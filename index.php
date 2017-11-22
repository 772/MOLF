<!DOCTYPE html>
<html>
<head>
<style>
table { border-collapse:separate; border-spacing: 0 0.5em; }
body { font-family: arial; font-size: 14px; margin: 0; }
td { padding: 1em; background-color: #9ccc65; }
tr {outline: thin solid black; padding-bottom: 1em;}
input, select {
    padding: 12px 20px;
    margin: 8px 0;
    display: inline-block;
    border: 1px solid #ccc;
    box-sizing: border-box;
}
input[type=submit] {
    background-color: #a1887f;
    color: #000;
    padding: 14px 20px;
    margin: 8px 0;
    border: none;
    cursor: pointer;
}
input[type=submit]:hover { background-color: #8d6e63; }
.teil {
	padding: 1em 10em;
}
footer {
	background-color: #8d6e63;
	color: white;
}
</style>
</head>
<body>
<?php
function randomKey($length) {
  $key = "";
  $pool = array_merge(range(0,9), range('a', 'z'),range('A', 'Z'));
  for($i=0; $i < $length; $i++)
    $key .= $pool[mt_rand(0, count($pool) - 1)];
  return $key;
}
$bestaetigungen = "molf/bestaetigungen/";
/* Alle Bestätigungen löschen die älter als 10 Minuten sind. */
foreach (glob($bestaetigungen."*") as $file) {
if(time() - filectime($file) > 600){
    unlink($file);
    }
}
$geladen = false;
$existiert = false;
$nutzer = "";
$besitzS = 2400;
if (!file_exists("molf/konten/"))
    mkdir("molf/konten/", 0777, true);
if (!file_exists($bestaetigungen))
    mkdir($bestaetigungen, 0777, true);
if (isset($_POST["email"]) && $_POST["email"] != "") {
  $geladen = true;
  $nutzer = strtolower ($_POST["email"]);
  if (file_exists("molf/konten/" . $nutzer)) {
    $existiert = true;
  }
}
else if (isset($_POST["minuten"])) {
  $rnd = randomKey(60);
  $shortfilename = $rnd.".php";
  $filename = $bestaetigungen.$shortfilename;
  $myfile = fopen($filename, "w");
$script = "
<html>
<head>
<meta http-equiv=\"refresh\" content=\"10; URL=../../index.php\">
</head>
<?php
\$alterbesitzer = \"".strtolower($_POST["alterbesitzer"])."\";
\$min = \"".$_POST["minuten"]."\";
\$schoepfer = \"".strtolower($_POST["schoepfer"])."\";
\$empf = \"".strtolower($_POST["empf"])."\";
\$filename = \"../konten/\".\$alterbesitzer;
if (\$min < 0)
	die(\"<b>Fehlerhafte Eingabe</b>: Negativer Betrag.<br><br><a href=\\\"../../index.php\\\">Zurück</a>\");
if (\$alterbesitzer === \$schoepfer) {
	if (!file_exists(\"../konten/\".\$alterbesitzer))
	mkdir(\"../konten/\".\$alterbesitzer, 0777, true);
	\$minrest = 2400;
	\$di = new RecursiveDirectoryIterator(\"../konten/\".\$alterbesitzer);
	foreach (new RecursiveIteratorIterator(\$di) as \$filename => \$file) {           
		if ((substr(\$file, -1) != '.') && (substr(\$file, -2) != '..')) {
			if (filesize(\$filename) != 0) continue;
			\$minrest -= file_get_contents(\"../konten/\".\$file->getFilename().\"/\".\$alterbesitzer);
		}
	}
	if (\$min > \$minrest)
		die(\"<b>Fehlerhafte Eingabe</b>: \".\$alterbesitzer.\" hat nicht genug von seinem eigenen Geld.<br><br><a href=\\\"../../index.php\\\">Zurück</a>\");
}
else {
    \$filename = \"../konten/\".\$alterbesitzer.\"/\".\$schoepfer;
    if (!file_exists(\$filename))
      die(\"<b>Fehlerhafte Eingabe</b>: \".\$alterbesitzer.\" besitzt kein Geld von \".\$schoepfer.\"<br><br><a href=\\\"../../index.php\\\">Zurück</a>\");
    \$min2 = file_get_contents(\$filename);
    if (\$min > \$min2)
      die(\"<b>Fehlerhafte Eingabe</b>: \".\$alterbesitzer.\" besitzt nicht genug Geld von \".\$schoepfer.\"<br><br><a href=\\\"../../index.php\\\">Zurück</a>\");
    \$minneu = \$min2 - \$min;
    if (\$minneu == 0) {
      unlink(\$filename);
      unlink(\"../konten/\".\$schoepfer.\$alterbesitzer);
    }
    else {
      \$myfile = fopen(\$filename, \"w\");
      fwrite(\$myfile, \$minneu);
      fclose(\$myfile);
    }
  }
  
  \$filename = \"../konten/\".\$empf.\"/\".\$schoepfer;
  if (!file_exists(\"../konten/\".\$empf))
    mkdir(\"../konten/\".\$empf, 0777, true);
  if (file_exists(\$filename)) {
    \$min = \$min + file_get_contents(\$filename);
  }
  \$myfile = fopen(\$filename, \"w\");
  fwrite(\$myfile, \$min);
  fclose(\$myfile);
  
  \$filename = \"../konten/\".\$schoepfer.\"/\".\$empf;
  if (!file_exists(\$filename)) {
    \$myfile = fopen(\$filename, \"w\");
    fclose(\$myfile);
  }
  
  die(\"Transaktion erfolgreich abgeschlossen.<br>Vielen Dank.<br><br><a href=\\\"../../index.php\\\">Zurück</a>\");
  unlink(__FILE__);
?>
</html>";
  fwrite($myfile, $script);
  fclose($myfile);
  
  /* Jetzt E-Mail senden. */
  mail($_POST["alterbesitzer"], "Bestätigung Transaktion", "Mit dem Öffnen des Links wird Ihre Transaktion bestätigt. Wenn Sie diese nicht angefordert haben, kann diese E-Mail ignoriert werden. Der Link ist nur 10 Minuten gültig.\n\nhttp://nothbachtal.de/".$filename, "From: MOLF <noreply@nothbachtal.de>");
  die("Es wurde eine Bestätigungs-E-Mail an ".$_POST["alterbesitzer"]." versendet. Sobald der darin enthaltene Link aufgerufen wird, wird die angeforderte Transaktion, falls möglich, ausgeführt. Der Link ist nur 10 Minuten gültig.<br><br><a href=\"index.php\">Zurück</a>");
}
?>

<div class="teil">
<h2>MOLF - Mailbasiertes Offenes Limitiertes Freigeld</h2>
<form action="index.php" method="POST">
<p><input class="input" type="email" name="email" required></p>
<p><input class="button" type="submit" value="E-Mail Konto laden"></p>
</form>
<h3><?php if ($geladen) echo strtolower($_POST["email"]); else echo "Keine E-Mail geladen."; ?></h3>
<p><a href="#fremde">Eigene Arbeitszeit im Umlauf</a></p>
<p><a href="#eigene">Arbeitszeit im Besitz</a></p>
<p><a href="#ueberweisen">Arbeitszeit überweisen</a></p>
<p><a href="#whitepaper">Whitepaper</a></p>

<h3 id="fremde">Eigene Arbeitszeit im Umlauf</h3>
<table>
<?php
if ($existiert) {
  $di = new RecursiveDirectoryIterator("molf/konten/".$nutzer);
  foreach (new RecursiveIteratorIterator($di) as $filename => $file) {           
    if ((substr($file, -1) != '.') && (substr($file, -2) != '..')) {
      if (filesize($filename) != 0) continue;
      $betrag = file_get_contents("molf/konten/".$file->getFilename()."/".$nutzer);
      $besitzS -= $betrag;
      echo "<tr><td>".$betrag."</td><td>Minuten Arbeit von ".$nutzer.".</td><td>Aktueller Besitzer ist ".$file->getFilename().".</td></tr>";
    }
  }
}
?>
</table>
<h3 id="eigene">Arbeitszeit im Besitz</h3>
<table>
<?php
if ($geladen) {
  if ($besitzS > 0)
    echo "<tr><td>".$besitzS."</td><td>Minuten Arbeit von ".$nutzer."</td><td>Aktueller Besitzer ist ".$nutzer.".</td></tr>";
  if ($existiert) {
    $di = new RecursiveDirectoryIterator("molf/konten/".$nutzer);
    foreach (new RecursiveIteratorIterator($di) as $filename => $file) {           
      if ((substr($file, -1) != '.') && (substr($file, -2) != '..')) {
        if (filesize($filename) == 0) continue;
        echo "<tr><td>".file_get_contents($filename)."</td><td>Minuten Arbeit von ".$file->getFilename()."</td><td>Aktueller Besitzer ist ".$nutzer.".</td></tr>";
      }
    }
  }
}
?>
</table>
<h3 id="ueberweisen">Arbeitszeit überweisen</h3>
<form method="POST" action="index.php">
<p>Absender <input type="email" name="alterbesitzer" value="<?php echo $nutzer; ?>" required> versendet <input name="minuten" min="1" max="2400" type="number" value="0" required> Minuten Arbeit von <input type="email" name="schoepfer" required> an den Empfänger <input type="email" name="empf" required></p>
<p><input type="submit" value="Überweisen"></p>
</form>
<h3 id="whitepaper">Whitepaper</h3>
<p>Diese Währung funktioniert <b>mailbasiert</b>, sodass jedes E-Mail Konto auf der Welt ohne sich jemals registrieren zu müssen standartmäßig 2400 Arbeitsminuten bzw. 40 Arbeitsstunden gutgeschrieben hat und frei über diese verfügen kann. Man benötigt somit kein Passwort und keine Profilangaben, stattdessen wird zur Identifikation vor jeder Transaktion ein Bestätigungslink an die jeweilige E-Mail gesendet. Wird diese Bestätigung nicht bestätigt, wird die gewünschte Transaktion niemals ausgeführt.</p>
<p>MOLF hat einen <b>offenen</b> Quellcode auf <a href="https://github.com/772/MOLF">Github</a>. Das Grundgerüst von MOLF ist minimalistisch und besteht nur aus dieser einzigen PHP-Datei, welche natürlich beliebig modifiziert und aufgehübscht werden kann. Gerade weil der Code dahinter so klein und einfach ist, ist die Währung wenigstens auch leicht verständlich, was bei einigen Kryptowährungen wie Bitcoin nicht der Fall ist, da sein Blockchain zwar leicht grob erklärt ist, im Detail aber sehr komplex und kompliziert ist. Dank der Verständlichkeit kann also auch der Laie prüfen, ob er den Source-Code sinnvoll findet.</p>
<p>Um Inflation zu vermeiden ist diese Währung <b>limitiert</b>, das heißt man kann von seiner eigenen Währung nicht mehr als seine 40 Stunden schöpfen.</p>
<p>Zudem ist diese Währung eine spezielle Form von <b>Freigeld</b>. Freigeld ist sogenanntes "Fließendes Geld", welches einen automatischen Negativzins auf Geld legt, um die Umlaufgeschwindigkeit zu erhöhen und das sinnlose Geldhorten zu unterbinden. Das funktioniert, indem am 1. Tag jeden Monats 1% der Geldmenge ihren originalen Schöpfer zurücküberwiesen werden.</p>
</div>

<footer>
<div class="teil">
<p><a href="impressum.php">Impressum</a></p>
</div>
</footer>

</body>
</html>

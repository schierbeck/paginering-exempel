<?php

/**
 * Pagineringsexempel
 *
 * Exemplet utgår från att det finns:
 * 1) En databas som heter 'paginering'
 * 2) En tabell som heter 'post'
 * 3) Kolumnerna id, title och content under tabellen post
 * 4) Minst ett inlägg mer en vad som är valt som limit ($limit) för att pagineringen ska visas
 */

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASSWORD', 'root');
define('DB_NAME', 'paginering');

@$mysqli = new mysqli( DB_HOST, DB_USER, DB_PASSWORD, DB_NAME );

if( $mysqli->connect_error ) {
	trigger_error('<h1>Database connection failed :-(</h1>' . $mysqli->connect_error, E_USER_ERROR);
}
?>

<!doctype html>
<html lang="sv">
<head>
	<meta charset="UTF-8">
	<title>Pagineringsexempel</title>
	<style>
		.current_page { color: red; }
	</style>
</head>
<body>

<?php
/* PAGINERING  block 1 (ska ligga innan nästa query då denna använder sig av variabler från denna */
// Vi börjar på 0, dvs from första inlägget (baserat på ordningen vi sen sätter i blogginläggs-SQL).
$start = 0;
// Antal blogginlägg vi visar per sida
$limit = 5;
// Är $_GET['page'] satt? Dvs finns ?page= med i adressfältet?
if( isset($_GET['page']) ) {
	// Sparar $_GET['page'] i en variabel för att det blir lättare att läsa.
	$current_page = $_GET['page'];
	// Vilka blogginlägg vi ska visa (beroende på sida) baseras på $current_page gånger antalet tillåtna blogginlägg ($limit)
	// - 1 för att vi börjar på 0 ($start) så sida 1 ska vara lika med 0, 2 vara lika med 1 osv.
	$start = ($current_page - 1) * $limit;
}
// Välj alla id från filmer (id går snabbast att hämta)
$pagination_sql = 'SELECT id from post';
if( $stmt = $mysqli->prepare($pagination_sql) ) {
	$stmt->execute();
	// store_results använder vi för att num_rows ska bli tillgänglig
	$stmt->store_result();
	// Spara ner antal rader (antal inlägg) i variabeln $rows_amount
	$rows_amount = $stmt->num_rows;
	// Antal sidor våra poster ska fördelas över
	$pages_amount = ($rows_amount / $limit) +1;
	// Stänger prepared statement
	$stmt->close();
}
/* PAGINERING block 1 slutar här */

/* POST SQL */
$post_sql = 'SELECT title, content FROM post LIMIT ?, ?';
if( $stmt = $mysqli->prepare($post_sql) ) {
	// Lägg märke till att vi i bind_param() använder oss av variabler ($start och $limit) vi skapade för paginering tidigare.
	$stmt->bind_param('ii', $start, $limit);
	$stmt->execute();
	$stmt->bind_result($title, $content);
?>

	<?php while( $stmt->fetch() ): ?>
		<h1><?php echo $title; ?></h1>
		<p><?php echo $content; ?></p>
	<?php endwhile; ?>

	<?php
	$stmt->close();
}
?>

<!-- PAGINERING block 2 -->
<!-- Om det finns färre rader än vad $limit är, exempelvis om det finns 4 inlägg och 5 inlägg får visas per sida så visas ingen paginering. -->
<?php if( $limit < $rows_amount ): ?>
	<!-- Loopar igenom antalet sidor blogginläggen ska fördelas över. -->
	<?php for($i = 1; $i < $pages_amount; $i++): ?>
		<!-- Om	$current_page är satt och $current_page är samma som $i ELLER om $current_page inte är satt och $i är samma som 1, så lägger vi till klasses current_page som med css gör siffran röd. -->
		<!-- Länken leder till samma sida ($_SERVER['PHP_SELF']) men skickar sidnummer (med $_GET['page']) vilket queryn ovan ($post_sql) hämtar upp och därefter bestämmer ilka inlägg som ska visas. -->
		<a class="<?php if( isset( $current_page ) && $current_page == $i || !isset($current_page) && $i == 1 ) { echo 'current_page'; } ?>" href="<?php echo $_SERVER['PHP_SELF']; ?>?page=<?php echo $i; ?>"><?php echo $i; ?></a>
	<?php endfor; ?>
<?php endif; ?>
<!-- PAGINERING block 2 slutar här -->

<?php $mysqli->close(); ?>

</body>
</html>
<?php
//Entrez ici les informations de votre base de données et le nom du fichier de sauvegarde.
$mysqlUserName ='XXX';
$mysqlPassword ='XX';
$mysqlHostName ='XXXXX';
$filedestination = "C:\wamp64\www\script_backup\backups\\";
$mysqldumplocation = "C:\wamp64\bin\mysql\mysql5.7.23\bin\mysqldump";
$date = (date("d") . date("m") . date("Y")  . date("H") . date("i") . date("s"));
$filesnames = scandir($filedestination);
$nbrdejour = 3;
 
try
{
   $bdd = new PDO('mysql:host='.$mysqlHostName.';charset=utf8', ''.$mysqlUserName.'', ''.$mysqlPassword.''); // connexion à  la base de données
}
catch (Exception $e)
{
        die('Erreur : ' . $e->getMessage());
}

function dateDiff($date1, $date2){
    $diff = abs($date1 - $date2); // abs pour avoir la valeur absolute, ainsi éviter d'avoir une différence négative
    $retour = array();
 
    $tmp = $diff;
    $retour['second'] = $tmp % 60;
 
    $tmp = floor( ($tmp - $retour['second']) /60 );
    $retour['minute'] = $tmp % 60;
 
    $tmp = floor( ($tmp - $retour['minute'])/60 );
    $retour['hour'] = $tmp % 24;
 
    $tmp = floor( ($tmp - $retour['hour'])  /24 );
    $retour['day'] = $tmp;
 
    return $retour;
}

$req = $bdd->prepare('SHOW DATABASES'); 
$req->execute();

$i = 0;
$ToUpdate = array();
$ToDelete = array();
$ToNotUpdate = array();

foreach ($filesnames as $value) 
{
if($i > 1) // scandir retourne "...." pour les index [0] et [1].
	{
	$name =  substr($value, 0,(strlen($value)- 19 ));
    $dateSave =  substr($value, (strlen($value) - 18),14);
    $dateBonFormat = substr($dateSave, 4,4) . "-" . substr($dateSave, 2,2) . "-" . substr($dateSave, 0,2) . " " . substr($dateSave, 8,2) . ":" . substr($dateSave, 10,2) . ":" . substr($dateSave, 12,2);
    $now = date("Y-m-d H:i:s"); 
    $date1 = strtotime($now);
    $date2 = strtotime($dateBonFormat);
    $diff = $date1 - $date2;
    //echo $date1 . "	-	" . $date2 . "=" . $diff . "</br>";
	    if($diff < 86400 * $nbrdejour) // 3 jours en secondes = 259 200 secondes.
	    {
	    	//echo $name . " à jour" . "</br>";
	    	array_push($ToNotUpdate, $name); // si le backup est plus récent que 3 jours on le met dans la liste des backups à ne pas mettre à jour
	    }
	    else
	    {
	    	array_push($ToDelete, $name . "." . $dateSave . ".sql"); // Si le backup date de plus de 3 jours alors on le met dans le tableau des backups à supprimer
	    }
	array_push($ToUpdate, $name); // On le met dans le tableau des backup à mettre à jour
	}
$i++;
}


$done = false; 
while ($donnees = $req->fetch()) // Toutes les bases de données que nous allons traiter
{
	for ($i=0; $i < count($ToNotUpdate); $i++) {  // On s'occupe d'abord des bases de données à ne pas mettre à jour.
		if($donnees['Database'] == $ToNotUpdate[$i])
		{
			echo $donnees['Database']. "   " . "déjà sauvegardée il y a moins de ".$nbrdejour." jours. " . "</br>";
			$done = true;
		}
	}
	for ($i=0; $i < count($ToUpdate); $i++) {  // On met à jour les bases de données qui dépassent les 3 jours d'ancienneté 
		if($donnees['Database'] == $ToUpdate[$i])
		{
			echo $donnees['Database'] . "    " . "sauvegardée " . "</br>";
			unlink($filedestination.$ToDelete[$i]);
			echo $ToDelete[$i] . " effacé " . "</br>";
			$format =  $donnees['Database'].".".$date.".sql";
			exec(''.$mysqldumplocation.' --user='.$mysqlUserName.' --password='.$mysqlPassword.' --host='.$mysqlHostName.' '.$donnees["Database"].' > '.$filedestination . $format.' ');
			echo $donnees['Database'] . "mise à jour" . "</br>";
			$done = true;
		}
	}
	if($done == false)
	{
	echo "la base" . $donnees['Database']. "    " . "a été sauvegardée   " . "</br>";
	$format =  $donnees['Database'].".".$date.".sql";
	exec(''.$mysqldumplocation.' --user='.$mysqlUserName.' --password='.$mysqlPassword.' --host='.$mysqlHostName.' '.$donnees["Database"].' > '.$filedestination . $format.' ');
	}
		    
}

?>
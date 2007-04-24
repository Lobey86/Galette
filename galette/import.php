<? 

/* import.php
 * - Import fichier CSV
 * Copyright (c) 2007 Olivier Perron aka Flattwin :-)
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 *
 */
	include("includes/config.inc.php"); 
	include(WEB_ROOT."includes/database.inc.php"); 
	include(WEB_ROOT."includes/functions.inc.php"); 
	include(WEB_ROOT."includes/lang.inc.php"); 
	include(WEB_ROOT."includes/session.inc.php"); 
	
	if ($_SESSION["logged_status"]==0) 
		header("location: index.php");
	if ($_SESSION["admin_status"]<FULL_ADMIN) 
		header("location: voir_adherent.php");

	include("header.php");
?>
<H1 class="titre"><? echo _T("Import fichier CSV"); ?></H1>
<!-- Formulaire -->
<!-- Attention, ne de ne pas oublier le  enctype="multipart/form-data" -->
<form method="POST" action="import.php" enctype="multipart/form-data">
<!-- Limiter la taille des fichiers à 500Ko -->
<input type="hidden" name="MAX_FILE_SIZE" value="500000" /> 
<fieldset>
<legend><? echo _T("Fichier CSV à importer"); ?></legend>
<!-- champs d'envoi de fichier, de type file -->
<p><label for="fichier"><? echo _T("Fichier"); ?> : </label><input type="file" name="csvfile" /></p>
<!-- separateur -->
<? echo _T("Caractère de séparation"); ?> : <select name = 'separator'>
  <option value="comma">, (<? echo _T("virgule"); ?>)</option>
  <option value="semicol">; (<? echo _T("point-virgule"); ?>)</option>
</select>
<!-- bouton d'envoi -->
<p><input type="submit" name="envoi" value=<? echo _T("Importer"); ?> /></p>
</legend>
</fieldset>
</form>

<?php
$csv_sep=',';
//$csv_sep=';';

if (isset($_FILES['csvfile']))
{
$DB = ADONewConnection(TYPE_DB);
$DB->debug = false;
if(!@$DB->Connect(HOST_DB, USER_DB, PWD_DB, NAME_DB)) die("No database connection...");
# Statuts: libelle_statut[id_statut] (President, Tresorier, ... , Membre actif (4), Ancien (7), Non membre (9),....)
# Compte: activite_adh: 1 -> Actif, 0 -> Inactif
$today = date('Y-m-d');
$row = 1;

if (isset($_POST['separator'])) {
	if ($_POST['separator'] == 'comma')
		$csv_sep = ',';
	if ($_POST['separator'] == 'semicol')
		$csv_sep = ';';
}

$file = $_FILES['csvfile']['tmp_name'];
$handle = fopen($file, "r");
if (! $handle) {
	echo "<p>"._T("Erreur ouverture du fichier").": $file<br /></p>\n";
	exit(0);
}
else {
	echo "<p>"._T("Traitement de").": ".$_FILES['csvfile']['name']." (".$file.")<br /></p>\n";
}
echo "<p>"._T("Caractère de séparation").": $csv_sep<br /></p>\n";

$header = fgetcsv($handle, 1024, $csv_sep);
$num = count($header);
echo "<p> $num fields in header line (row $row): <br /></p>\n";
$row++;
while (($data = fgetcsv($handle, 1024, $csv_sep)) !== FALSE) {
    $num = count($data);
    echo "<p> $num fields in line $row: <br /></p>\n";
    $row++;
    for ($c=0; $c < $num; $c++) {
		if ($data[$c] == '')
			$data[$c] = 'NULL';
#		$data[$header[$c]] = preg_replace('/\'/', '\\\'', $data[$c]); // replace with \'
		$data[$header[$c]] = preg_replace('/\'/', '\'\'', $data[$c]); // replace with ''
		unset($data[$c]);
	}

	if ($data["id_adh"] != 'NULL')
	{
		// update existing field

                 $fields = array('id_groupe', 'nom_adh', 'prenom_adh', 'titre_adh', 'ddn_adh', 'ldn_adh', 'adresse_adh', 'adresse2_adh', 'cp_adh', 'ville_adh', 'pays_adh', 'tel_adh', 'tel_pro_adh', 'gsm_adh', 'email_adh', 'email_pro_adh', 'lic_adh', 'date_licence', 'medecin_certif', 'date_certif', 'dif_tel', 'dif_email', 'dif_image', 'enveloppe', 'photo', 'attestation');

		$requete = "UPDATE ".PREFIX_DB."adherents SET ";
		//$requete .= "date_crea_adh='".$today."', ";
		$requete .= "id_statut='4', activite_adh='1'";

                foreach($fields as $value)
                {
                  if ($data[$value] == 'NULL')
                    $requete .= ", ".$value."=NULL";
                  else
                    $requete .= ", ".$value."='".$data[$value]."'";
                }
		$requete .= " WHERE id_adh='".$data["id_adh"]."'";
		echo $requete."<br>\n";
		$res = $DB->Execute($requete);
		if (!$res) 
			echo $DB->ErrorMsg()."<br>\n";

		echo _T("Mise à jour de")." (".$data["id_adh"].") ".$data["nom_adh"].", ".$data["prenom_adh"]."<br>\n";
	}
	else
	{
		// create new record
		$mdp = makeRandomPassword();
		$login ="";
		foreach(split('[ -]', strtolower($data["prenom_adh"])) as $value)
			$login.=$value{0};
		$login.=preg_replace('/[ -]/', '', strtolower($data["nom_adh"]));

                if ($data['date_crea_adh']=='NULL')
                  $data['date_crea_adh']="2006-09-01";
                //  $data['date_crea_adh']=$today;
                $fields = array('id_groupe', 'nom_adh', 'prenom_adh', 'titre_adh', 'ddn_adh', 'ldn_adh', 'adresse_adh', 'adresse2_adh', 'cp_adh', 'ville_adh', 'pays_adh', 'tel_adh', 'tel_pro_adh', 'gsm_adh', 'email_adh', 'email_pro_adh', 'date_crea_adh', 'lic_adh', 'date_licence', 'medecin_certif', 'date_certif', 'dif_tel', 'dif_email', 'dif_image', 'enveloppe', 'photo', 'attestation');

		$requete  = "INSERT INTO ".PREFIX_DB."adherents ";
                $requete .= "(id_statut, activite_adh, login_adh, mdp_adh";
                foreach($fields as $value)
                {
                  $requete .= ", ".$value;
                }
                $requete .=") ";
                $requete .= "VALUES ('4', '1', '".$login."', '".$mdp."'";
                foreach($fields as $value)
                {
                  if ($data[$value] == 'NULL')
                    $requete .= ", NULL";
                  else
                    $requete .= ", '".$data[$value]."'";
                }
                $requete .=")";


		echo $requete."<br>\n";
		$res = $DB->Execute($requete);
		if (!$res) 
			echo $DB->ErrorMsg()."<br>\n";

		$result = $DB->Execute("SELECT id_adh FROM ".PREFIX_DB."adherents WHERE nom_adh='".$data["nom_adh"]."' AND prenom_adh='".$data["prenom_adh"]."' AND ddn_adh='".$data["ddn_adh"]."'");
		while (!$result->EOF)
		{
			$id_adh=$result->fields[0];
			echo _T("Création de")." (".$id_adh.") ".$data["nom_adh"].", ".$data["prenom_adh"]."<br>\n";
			$result->MoveNext();
		}
		$result->Close();
	}
}
fclose($handle);
$DB->Close();
unlink($file);
}

  include("footer.php"); 
?>

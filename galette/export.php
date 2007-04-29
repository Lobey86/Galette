<? 

/* export.php
 * - Export fichier CSV
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
	include_once(WEB_ROOT.'/includes/adodb/toexport.inc.php');
	include_once(WEB_ROOT.'/includes/adodb/rsfilter.inc.php');
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
<H1 class="titre"><? echo _T("Export fichier CSV"); ?></H1>

<?
function age($naiss)  {
  list($annee, $mois, $jour) = split('[-.]', $naiss);
  $today['mois'] = date('n');
  $today['jour'] = date('j');
  $today['annee'] = date('Y');
  $annees = $today['annee'] - $annee;
  if ($today['mois'] <= $mois) {
    if ($mois == $today['mois']) {
      if ($jour > $today['jour'])
        $annees--;
      }
    else
      $annees--;
    }
  return $annees;
}

function do_filter(&$arr,$rs)
{
# Sexe H/F de l'adhérent
	if ($arr[2] == 1)
		$arr[2] = "H";
	else
		$arr[2] = "F";
# Age de l'adhérent
	$arr[3] = age($arr[3]);

# Type de license
	if ($arr[5] == 1)
		$arr[5] = "FFRS Loisir Randonnée";
	else
		$arr[5] = "FFRS Compétion Course";
}


	$DB = ADONewConnection(TYPE_DB);
	$DB->debug = false;
	if(!@$DB->Connect(HOST_DB, USER_DB, PWD_DB, NAME_DB)) die("No database connection...");

        // Export fichier adhérents groupe par groupe
        $result = $DB->Execute("SELECT id_groupe, libelle_groupe FROM ".PREFIX_DB."groupes");

	while (!$result->EOF)
        {
            $filename=$result->fields[1].".csv";
            $fp = fopen($filename, "w");

#            $fields = "id_adh,id_statut,nom_adh,prenom_adh,pseudo_adh,titre_adh,ddn_adh,ldn_adh,adresse_adh,adresse2_adh,cp_adh,ville_adh,pays_adh,tel_adh,tel_pro_adh,gsm_adh,email_adh,email_pro_adh,url_adh,icq_adh,msn_adh,jabber_adh,info_adh,info_public_adh,prof_adh,login_adh,mdp_adh,date_crea_adh,activite_adh,bool_admin_adh,bool_exempt_adh,bool_display_info,date_echeance,id_groupe,id_lic,lic_adh,date_licence,id_categorie,medecin_certif,date_certif,dif_tel,dif_email,dif_image,enveloppe,photo,attestation,participation_vet,referent";
            $fields = "id_adh,nom_adh,prenom_adh,titre_adh,ddn_adh,ldn_adh,adresse_adh,adresse2_adh,cp_adh,ville_adh,pays_adh";
            $fields.= ",tel_adh,tel_pro_adh,gsm_adh,email_adh,email_pro_adh";
            $fields.= ",date_crea_adh,lic_adh,date_licence,medecin_certif,date_certif,dif_tel,dif_email,dif_image,enveloppe,photo,attestation";

            $requete = "SELECT ".$fields." FROM ".PREFIX_DB."adherents WHERE id_groupe=".$result->fields[0]." AND id_statut <> '7'";

            $rs = $DB->Execute($requete);

            #print "<pre>";
            
            #print rs2csv($rs); # return a string, CSV format
            #print '<hr>';

            #$rs->MoveFirst(); # note, some databases do not support MoveFirst
            #print rs2tab($rs,true);    # return a string, tab-delimited
                                        # false == suppress field names in first line
            #print '<hr>';

            #$rs->MoveFirst();
            #rs2tabout($rs); # send to stdout directly (there is also an rs2csvout function)
            #print "</pre>";

            $rs->MoveFirst();
            if ($fp) {
              rs2csvfile($rs, $fp); # write to file (there is also an rs2tabfile function)
              fclose($fp);
            }
            $records=$rs->PO_RecordCount();
            $rs->Close();
            $result->MoveNext();
            
            echo "<br><a href=\"".$filename."\">".$filename."</a> (".$records." records)<br>\n";
        }
       	$result->Close();

        // Export fichier adhérents tous groupes confondus
        $requete = "SELECT * FROM ".PREFIX_DB."adherents";
        $rs = $DB->Execute($requete);
        $filename="galette_adherents_full.csv";
        $fp = fopen($filename, "w");
        $rs->MoveFirst();
        if ($fp) {
          rs2csvfile($rs, $fp); # write to file (there is also an rs2tabfile function)
          fclose($fp);
        }
        $records=$rs->PO_RecordCount();
        $rs->Close();
        echo "<br><a href=\"".$filename."\">".$filename."</a> (".$records." records)<br>\n";

        // Export fichier pour statistiques mairie
        $fields = "nom_adh,prenom_adh,titre_adh,ddn_adh,ville_adh,id_lic";
        $requete = "SELECT ".$fields." FROM ".PREFIX_DB."adherents WHERE id_statut <> '7'";
        $rs = $DB->Execute($requete);
	$rs = RSFilter($rs,'do_filter');
        $filename="stats_mairie.csv";
        $fp = fopen($filename, "w");
        $rs->MoveFirst();
        if ($fp) {
	  fwrite($fp, "NOM; PRENOM; SEXE; AGE; VILLE; LICENCE\n");
          rs2csvfile($rs, $fp, false); # write to file (there is also an rs2tabfile function)
          fclose($fp);
        }
        $records=$rs->PO_RecordCount();
        $rs->Close();
           
        echo "<br><a href=\"".$filename."\">".$filename."</a> (".$records." records)<br>\n";


	$DB->Close();

  include("footer.php"); 
?>

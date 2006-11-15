<?
/* voir_adherent.php
 * - Visualisation d'une fiche adhérent
 * Copyright (c) 2004 Frédéric Jaqcuot
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

	// Si on est vrai super admin, alors l'id_adh est mis à ""
	//if ($_SESSION["admin_status"]==SUPER_ADMIN)
	//if ($_SESSION["logged_nom_adh"]==_T("Administrateur"))
	if ($_SESSION["admin_status"]==SITE_ADMIN)
		$id_adh = "";	
	else
		$id_adh = $_SESSION["logged_id_adh"];

	$id_groupe = "";
	if (isset($_GET["id_adh"]))
		if (is_numeric($_GET["id_adh"]))
		{
			$id_adh = $_GET["id_adh"];
			$requete = "SELECT id_groupe
					    FROM ".PREFIX_DB."adherents
					    WHERE id_adh=". $id_adh;
			$result = &$DB->Execute($requete);
			if (!$result->EOF)
				$id_groupe = $result->fields[0];
		}
	
	// Si c'est un user qui est loggé: on va à sa fiche
	if ($_SESSION["admin_status"]==0)
		$id_adh = $_SESSION["logged_id_adh"];

	// Si c'est un admin qui est loggé, mais le groupe n'est pas celui dont il est administrateur: on va à sa fiche
	if (($_SESSION["admin_status"]<FULL_ADMIN)&&($_SESSION["admin_status"] != $id_groupe))
		$id_adh = $_SESSION["logged_id_adh"];
					
	if ($id_adh=="")
		header("location: index.php");

     //
    // Pré-remplissage des champs
   //  avec des valeurs issues de la base
  //

	$requete = "SELECT * FROM ".PREFIX_DB."adherents WHERE id_adh=$id_adh";
	$result = &$DB->Execute($requete);
        if ($result->EOF)
		header("location: index.php");

	// recuperation de la liste de champs de la table
  $fields = &$DB->MetaColumns(PREFIX_DB."adherents");
	while (list($champ, $proprietes) = each($fields))
	{
		$val="";
		$proprietes_arr = get_object_vars($proprietes);
		// on obtient name, max_length, type, not_null, has_default, primary_key,

		// déclaration des variables correspondant aux champs
		// et reformatage des dates.

		// on doit faire cette verif pour une enventuelle valeur "NULL"
		// non renvoyée -> ex: pas de tel
		// sinon on obtient un warning
		if (isset($result->fields[$proprietes_arr["name"]]))
			$val = $result->fields[$proprietes_arr["name"]];

		if($proprietes_arr["type"]=="date" && $val!="")
		{
			list($a,$m,$j)=split("-",$val);
			$val="$j/$m/$a";
		}

		$$proprietes_arr["name"] = htmlentities(stripslashes(addslashes($val)), ENT_QUOTES);
	}
	reset($fields);
	include("header.php");
?>
			<H1 class="titre"><? echo _T("Fiche adhérent"); ?></H1>
			<BLOCKQUOTE>
				<DIV align="center">
				<TABLE border="0">
					<TR>
						<TD bgcolor="#DDDDFF"><B><? echo _T("Nom :"); ?></B></TD>
<?
	$nom_adh_ext = "";
	switch($titre_adh)
	{
		case "1" :
			$nom_adh_ext .= _T("M.");
			break;
		case "2" :
			$nom_adh_ext .= _T("Mme.");
			break;
		default :
			$nom_adh_ext .= _T("Mlle.");
	}
	$nom_adh_ext .= " ".htmlentities(strtoupper(custom_html_entity_decode($nom_adh)), ENT_QUOTES)." ".ucfirst(strtolower($prenom_adh));
?>
						<TD bgcolor="#EEEEEE"><? echo $nom_adh_ext; ?></TD>
<?
	$image_adh = "";
	if (file_exists(WEB_ROOT . "photos/tn_" . $id_adh . ".jpg"))
	{
		$image_adh = "photos/tn_" . $id_adh . ".jpg";
		$image_adh_full = "photos/" . $id_adh . ".jpg";
	}
	elseif (file_exists(WEB_ROOT . "photos/tn_" . $id_adh . ".gif"))
	{
		$image_adh = "photos/tn_" . $id_adh . ".gif";
		$image_adh_full = "photos/" . $id_adh . ".gif";
	}
	elseif (file_exists(WEB_ROOT . "photos/tn_" . $id_adh . ".png"))
	{
		$image_adh = "photos/tn_" . $id_adh . ".png";
		$image_adh_full = "photos/" . $id_adh . ".png";
	}
	elseif (file_exists(WEB_ROOT . "photos/" . $id_adh . ".jpg"))
	{
		$image_adh = "photos/" . $id_adh . ".jpg";
		$image_adh_full = "photos/" . $id_adh . ".jpg";
	}
	elseif (file_exists(WEB_ROOT . "photos/" . $id_adh . ".gif"))
	{
		$image_adh = "photos/" . $id_adh . ".gif";
		$image_adh_full = "photos/" . $id_adh . ".gif";
	}
	elseif (file_exists(WEB_ROOT . "photos/" . $id_adh . ".png"))
	{
		$image_adh = "photos/" . $id_adh . ".png";
		$image_adh_full = "photos/" . $id_adh . ".png";
	}

	if ($image_adh != "")
	{
                if (function_exists("ImageCreateFromString"))
                        $imagedata = getimagesize($image_adh);
                else
                        $imagedata = array("130","");
		$photo_adh ="<A href=\"".$image_adh_full."\" target=\"_blank\"><IMG src=\"".$image_adh."?nocache".time()."\" border=\"1\" alt=\""._T("Photo")."\" width=\"".$imagedata[0]."\" width=\"".$imagedata[1]."\"></A>";
	}
	else
		$photo_adh = _T("[ pas de photo ]");

	if ($_SESSION["admin_status"]!=0)
		$rowspan_photo = "8";
	else
		$rowspan_photo = "5";
?>
						<TD colspan="2" rowspan="<? echo $rowspan_photo; ?>" align="center"><? echo $photo_adh; ?></TD>
					</TR>
					<TR>
						<TD bgcolor="#DDDDFF"><B><? echo _T("Pseudo :"); ?></B></TD>
						<TD bgcolor="#EEEEEE"><? echo $pseudo_adh; ?>&nbsp;</TD>
					</TR>
					<TR>
						<TD bgcolor="#DDDDFF"><B><? echo _T("Date de naissance :"); ?></B></TD>
						<TD bgcolor="#EEEEEE"><? echo $ddn_adh; ?>&nbsp;</TD>
					</TR>
					<TR>
						<TD bgcolor="#DDDDFF"><B><? echo _T("Statut :"); ?></B></TD>
<?
	$requete = "SELECT libelle_statut
								FROM ".PREFIX_DB."statuts
								WHERE id_statut=".$id_statut."
								ORDER BY priorite_statut";
	$result = &$DB->Execute($requete);
	if (!$result->EOF)
		$libelle_statut = _T($result->fields["libelle_statut"]);
	$result->Close();
?>
						<TD bgcolor="#EEEEEE"><? echo $libelle_statut ?>&nbsp;</TD>
					</TR>
					<TR>
						<TD bgcolor="#DDDDFF"><B><? echo _T("Profession :"); ?></B></TD>
						<TD bgcolor="#EEEEEE"><? echo $prof_adh; ?>&nbsp;</TD>
					</TR>
					<TR>
						<TD bgcolor="#DDDDFF"><B><? echo _T("Je souhaite apparaître dans la liste des membres :"); ?></B></TD>
						<TD bgcolor="#EEEEEE"><? if ($bool_display_info=="1") echo _T("Oui"); else echo _T("Non"); ?></TD>
					</TR>
<?
	if ($_SESSION["admin_status"]!=0)
	{
?>
					<TR>
						<TD bgcolor="#DDDDFF"><B><? echo _T("Compte :"); ?></B></TD>
						<TD bgcolor="#EEEEEE"><? if ($activite_adh=="1") 	echo _T("Actif"); else echo _T("Inactif"); ?></TD>
					</TR>
					<TR>
						<TD bgcolor="#DDDDFF"><B><? echo _T("Admin Galette :"); ?></B></TD>
						<TD bgcolor="#EEEEEE">
						<?
						if ($bool_admin_adh=="0")
							{echo _T("Non");}
						elseif ($bool_admin_adh >= FULL_ADMIN) 
							{echo _T("Oui (Tout groupe)");}
						else
						{
							$requete = "SELECT libelle_groupe
								FROM ".PREFIX_DB."groupes
								WHERE id_groupe=".$bool_admin_adh;
							$result = &$DB->Execute($requete);
							if (!$result->EOF)
								echo _T("Oui (".$result->fields["libelle_groupe"].")");
							$result->Close();
						}
						?></TD>						
					</TR>
					<TR>
						<TD bgcolor="#DDDDFF"><B><? echo _T("Référent :"); ?></B></TD>
						<TD bgcolor="#EEEEEE">
						<?
						if ($referent=="0")
							{echo _T("Non");}
						else
						{
							$requete = "SELECT libelle_groupe
								FROM ".PREFIX_DB."groupes
								WHERE id_groupe=".$referent;
							$result = &$DB->Execute($requete);
							if (!$result->EOF)
								echo _T("Oui (".$result->fields["libelle_groupe"].")");
							$result->Close();
						}
						?></TD>						
					</TR>
					<TR>
						<TD bgcolor="#DDDDFF"><B><? echo _T("Exempt de cotisation :"); ?></B></TD>
						<TD bgcolor="#EEEEEE"><? if ($bool_exempt_adh=="1") echo _T("Oui"); else echo _T("Non"); ?></TD>
					</TR>
<?
	}
?>
					<TR>
						<TD colspan="4">&nbsp;</TD>
					</TR>
					<TR>
						<TD bgcolor="#DDDDFF" valign="top"><B><? echo _T("Adresse :"); ?></B></TD>
						<TD bgcolor="#EEEEEE" colspan="3"><? echo $adresse_adh; ?>&nbsp;<BR><? echo $adresse2_adh; ?>&nbsp;</TD>
					</TR>
					<TR>
						<TD bgcolor="#DDDDFF"><B><? echo _T("Code Postal :"); ?></B></TD>
						<TD bgcolor="#EEEEEE"><? echo $cp_adh; ?>&nbsp;</TD>
						<TD bgcolor="#DDDDFF"><B><? echo _T("Ville :"); ?></B></TD>
						<TD bgcolor="#EEEEEE"><? echo $ville_adh; ?>&nbsp;</TD>
					</TR>
					<TR>
						<TD bgcolor="#DDDDFF"><B><? echo _T("Pays :"); ?></B></TD>
						<TD bgcolor="#EEEEEE"><? echo $pays_adh; ?>&nbsp;</TD>
						<TD bgcolor="#DDDDFF"><B><? echo _T("Tel :"); ?></B></TD>
						<TD bgcolor="#EEEEEE"><? echo $tel_adh; ?>&nbsp;</TD>
					</TR>
					<TR>
						<TD bgcolor="#DDDDFF"><B><? echo _T("GSM :"); ?></B></TD>
						<TD bgcolor="#EEEEEE"><? echo $gsm_adh; ?>&nbsp;</TD>
						<TD bgcolor="#DDDDFF"><B><? echo _T("Tel prof.:"); ?></B></TD>
						<TD bgcolor="#EEEEEE"><? echo $tel_pro_adh; ?>&nbsp;</TD>
					</TR>
					<TR>
						<TD bgcolor="#DDDDFF"><B><? echo _T("E-Mail :"); ?></B></TD>
						<TD bgcolor="#EEEEEE"><? if ($email_adh!="") { ?><A href="mailto:<? echo $email_adh; ?>"><? echo $email_adh; ?></A><? } ?>&nbsp;</TD>
						<TD bgcolor="#DDDDFF"><B><? echo _T("E-Mail prof.:"); ?></B></TD>
						<TD bgcolor="#EEEEEE"><? if ($email_pro_adh!="") { ?><A href="mailto:<? echo $email_pro_adh; ?>"><? echo $email_pro_adh; ?></A><? } ?>&nbsp;</TD>
					</TR>
					<TR>
						<TD bgcolor="#DDDDFF"><B><? echo _T("Site Web :"); ?></B></TD>
						<TD bgcolor="#EEEEEE"><? echo $url_adh; ?>&nbsp;</TD>
						<TD bgcolor="#DDDDFF"><B><? echo _T("ICQ :"); ?></B></TD> 
						<TD bgcolor="#EEEEEE"><? echo $icq_adh; ?>&nbsp;</TD> 
					</TR>
						<TD bgcolor="#DDDDFF"><B><? echo _T("Jabber :"); ?></B></TD>
						<TD bgcolor="#EEEEEE"><? echo $jabber_adh; ?>&nbsp;</TD>
						<TD bgcolor="#DDDDFF"><B><? echo _T("MSN :"); ?></B></TD>
						<TD bgcolor="#EEEEEE"><? if ($msn_adh!="") { ?><A href="mailto:<? echo $msn_adh; ?>"><? echo $msn_adh; ?></A><? } ?>&nbsp;</TD>
					 <TR>
					</TR>

					<TR>
						<TD colspan="4">&nbsp;</TD>
					</TR>
					<TR>
						<TD bgcolor="#DDDDFF"><B><? echo _T("Groupe :"); ?></B>&nbsp;</TD>
<?
	$requete = "SELECT libelle_groupe
								FROM ".PREFIX_DB."groupes
								WHERE id_groupe=".$id_groupe.""
								;
	$result = &$DB->Execute($requete);
	if (!$result->EOF)
		$libelle_groupe = _T($result->fields["libelle_groupe"]);
	$result->Close();
?>
						<TD bgcolor="#EEEEEE" colspan="3"><? echo $libelle_groupe; ?></TD>
					</TR>
					<TR>
						<TD colspan="4">&nbsp;</TD>
					</TR>
					<TR>
						<TD bgcolor="#DDDDFF"><B><? echo _T("Licence :"); ?></B></TD>
<?
	if ($id_lic != "")
	{
	  $requete = "SELECT libelle_type_lic
								FROM ".PREFIX_DB."types_licences
								WHERE id_type_lic=".$id_lic.""
								;
   	$result = &$DB->Execute($requete);
	  if (!$result->EOF)
	   	$libelle_licence = _T($result->fields["libelle_type_lic"]);
   	$result->Close();
   }
   else
     $libelle_licence = ""; 
?>
						<TD bgcolor="#EEEEEE"><? echo $libelle_licence; ?>&nbsp;</TD>
						<TD bgcolor="#DDDDFF"><B><? echo _T("N° :"); ?></B></TD>
						<TD bgcolor="#EEEEEE"><? echo $lic_adh; ?>&nbsp</TD>
					</TR>
					<TR>
						<TD bgcolor="#DDDDFF"><B><? echo _T("Date de saisie :"); ?></B>&nbsp;</TD>
						<TD bgcolor="#EEEEEE"><? echo $date_licence; ?></TD>
						<TD bgcolor="#DDDDFF"><B><? echo _T("Categorie :"); ?></B></TD>
<?
	if ($id_categorie != "")
	{
		$requete = "SELECT libelle_categorie
								FROM ".PREFIX_DB."categories
								WHERE id_categorie=".$id_categorie.""
								;
		$result = &$DB->Execute($requete);
		if (!$result->EOF)
			$libelle_categorie = _T($result->fields["libelle_categorie"]);
		$result->Close();
	}
	else
		$libelle_categorie = "";
?>
						<TD bgcolor="#EEEEEE"><? echo $libelle_categorie; ?>&nbsp;</TD>
					</TR>
						<TD bgcolor="#DDDDFF"><B><? echo _T("Date certif. médical :"); ?></B>&nbsp;</TD>
						<TD bgcolor="#EEEEEE"><? echo $date_certif; ?></TD>
						<TD bgcolor="#DDDDFF"><B><? echo _T("Médecin :"); ?></B>&nbsp;</TD>
						<TD bgcolor="#EEEEEE"><? echo $medecin_certif; ?></TD>
					</TR>
					<TR>
						<TD colspan="4">&nbsp;</TD>
					</TR>
					<TR>
						<TD bgcolor="#DDDDFF" colspan="4" align="center"><B><? echo _T("Autorisation de diffuser"); ?></B></TD>
					</TR>
					<TR>
						<TD bgcolor="#DDDDFF"><B><? echo _T("E-mail :"); ?></B></TD>
						<TD bgcolor="#EEEEEE"><? echo _T($dif_email); ?></TD>
						<TD bgcolor="#DDDDFF"><B><? echo _T("Tel :"); ?></B></TD>
						<TD bgcolor="#EEEEEE"><? echo _T($dif_tel); ?></TD>
					</TR>
					<TR>
						<TD bgcolor="#DDDDFF"><B><? echo _T("Photos :"); ?></B></TD>
						<TD bgcolor="#EEEEEE" colspan="3"><? echo _T($dif_image); ?></TD>
					</TR>
					<TR>
						<TD colspan="4">&nbsp;</TD>
					</TR>
					<TR>
						<TD bgcolor="#DDDDFF" colspan="4" align="center"><B><? echo _T("Eléments constitutifs du dossier"); ?></B></TD>
					</TR>
					<TR>
						<TD bgcolor="#DDDDFF"><B><? echo _T("Enveloppe :"); ?></B></TD>
						<TD bgcolor="#EEEEEE"><? echo _T($enveloppe); ?></TD>
						<TD bgcolor="#DDDDFF"><B><? echo _T("Photo(s) :"); ?></B></TD>
						<TD bgcolor="#EEEEEE"><? echo _T($photo); ?></TD>
					</TR>
<?
	if ($_SESSION["admin_status"]!=0)
	{
?>
					<TR>
						<TD colspan="4">&nbsp;</TD>
					</TR>
					<TR>
						<TD bgcolor="#DDDDFF"><B><? echo _T("Participation Club achat vêtement :"); ?></B></TD>
						<TD bgcolor="#EEEEEE" colspan="3"><? echo _T($participation_vet); ?></TD>
					</TR>
<?
			}
?>
					<TR>
						<TD colspan="4">&nbsp;</TD>
					</TR>
					<TR>
						<TD bgcolor="#DDDDFF"><B><? echo _T("Identifiant :"); ?>&nbsp;</B></TD>
						<TD bgcolor="#EEEEEE"><? echo $login_adh; ?></TD>
						<TD bgcolor="#DDDDFF"><B><? echo _T("Mot de passe :"); ?></B>&nbsp;</TD>
						<TD bgcolor="#EEEEEE"><? echo $mdp_adh; ?></TD>
					</TR>
<?
			$ajout_contrib="";
			if ($_SESSION["admin_status"]!=0)
			{
?>
					<TR>
						<TD bgcolor="#DDDDFF"><B><? echo _T("Date de création :"); ?></B>&nbsp;</TD>
						<TD bgcolor="#EEEEEE" colspan="3"><? echo $date_crea_adh; ?></TD>
					</TR>
					<TR>
						<TD bgcolor="#DDDDFF" valign="top"><B><? echo _T("Autres informations (admin) :"); ?></B></TD>
						<TD bgcolor="#EEEEEE" colspan="3"><? echo nl2br($info_adh); ?></TD>
					</TR>
<?
				$ajout_contrib = "&nbsp;&nbsp;&nbsp;<A href=\"ajouter_contribution.php?id_adh=".$id_adh."\">"._T("[ Ajouter une contribution ]")."</A>";
			}
?>
					<TR>
						<TD bgcolor="#DDDDFF" valign="top"><B><? echo _T("Autres informations :"); ?></B></TD>
						<TD bgcolor="#EEEEEE" colspan="3"><? echo nl2br($info_public_adh); ?></TD>
					</TR>
					<TR>
						<TD colspan="4" align="center"><BR><A href="ajouter_adherent.php?id_adh=<? echo $id_adh; ?>"><? echo _T("[ Modification ]"); ?></A>&nbsp;&nbsp;&nbsp;<A href="gestion_contributions.php?id_adh=<? echo $id_adh; ?>"><? echo _T("[ Contributions ]"); ?></A><? echo $ajout_contrib; ?></TD>
					</TR>
				</TABLE>
			</DIV>
			<BR>
		</BLOCKQUOTE>
<?
  include("footer.php")
?>

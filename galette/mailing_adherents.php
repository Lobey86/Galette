<? 

/* mailing_adherents.php
 * - Envoi de mails en masse
 * Copyright (c) 2003 Frédéric Jaqcuot
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

    /* Finalement, tout admin (site, full, ou admin de groupe a le droit de mailer à tout le monde ! */
    $apply_admin_policy = 0;


	if ($_SESSION["logged_status"]==0) 
		header("location: index.php");

	if ($_SESSION["admin_status"]==0) 
		header("location: voir_adherent.php");
	elseif (($apply_admin_policy)&&($_SESSION["admin_status"]<FULL_ADMIN))
		$_SESSION["filtre_adh_3"] = $_SESSION["admin_status"];

	// Taille max des fichiers (octets)
	$MFS=2*1024*1024;
		
	$mailing_adh = array();
	$nomail_adh = array();
	if (isset($_POST["mailing_adh"]))
		while (list($key,$value)=each($_POST["mailing_adh"]))
			$mailing_adh[]=$value;

	$mailing_corps = "";
	if (isset($_POST["mailing_corps"]))
		$mailing_corps = stripslashes($_POST["mailing_corps"]);

	$mailing_objet = "";
	if (isset($_POST["mailing_objet"]))
		$mailing_objet = stripslashes($_POST["mailing_objet"]);

	$mailing_replyto = "";
	if ($_SESSION["admin_status"]<SITE_ADMIN)
	{
		$requete[0] = "SELECT prenom_adh, nom_adh, email_adh, email_pro_adh
						FROM ".PREFIX_DB."adherents
						WHERE id_adh=" . $_SESSION["logged_id_adh"];
			$resultat = &$DB->Execute($requete[0]);
			if (!$resultat->EOF)
			{
				$mailing_replyto = $resultat->fields[0]." ".$resultat->fields[1];
				if ($resultat->fields[2] != "")
					$mailing_replyto .= " <".$resultat->fields[2].">";
				elseif ($resultat->fields[3] != "")
					$mailing_replyto .= " <".$resultat->fields[3].">";
				else
					$mailing_replyto = "";
			}
	}

	if (isset($_POST["mailing_replyto"]))
		$mailing_replyto = stripslashes($_POST["mailing_replyto"]);

	// Is the OS Windows or Mac or Linux: set EOL accordingly
	if (strtoupper(substr(PHP_OS,0,3)=='WIN')) {
		$eol="\r\n";
	} elseif (strtoupper(substr(PHP_OS,0,3)=='MAC')) {
		$eol="\r";
	} else {
		$eol="\n";
	}
	
	$mailing_headers = "From: ".PREF_EMAIL_NOM." <".PREF_EMAIL.">".$eol;
	if ($mailing_replyto != "")
		$mailing_headers .= "Reply-To: ".$mailing_replyto.$eol;
	// These two to help avoid spam-filters
	$mailing_headers .= "Message-ID: <".$now."LaGalette@".$_SERVER['SERVER_NAME'].">".$eol;
	$mailing_headers .= "X-Mailer: PHP v".phpversion().$eol;          

	$error_detected = "";
	
	$etape = 0;

	if (isset($_POST["attach_go"]))
	{
		if(isset($_FILES['mailing_userfile']))
		{
			if ($_FILES['mailing_userfile']['error'])
			{
				switch ($_FILES['mailing_userfile']['error']){
					case 1: // UPLOAD_ERR_INI_SIZE
					$error_detected .= "<LI>"._T("Le fichier dépasse la limite autorisée par le serveur (fichier php.ini) !")."</LI>";
					$mosize=$MFS/1024/1024;
					$error_detected .= "<LI>"._T("Le fichier attaché dépasse la taille permise de ".$mosize." Mo max.")."</LI>";
                   break;
                   case 2: // UPLOAD_ERR_FORM_SIZE
                   $error_detected .= "<LI>"._T("Le fichier dépasse la limite autorisée dans le formulaire HTML !")."</LI>";
                   break;
                   case 3: // UPLOAD_ERR_PARTIAL
                   $error_detected .= "<LI>"._T("L'envoi du fichier a été interrompu pendant le transfert !")."</LI>";
                   break;
                   case 4: // UPLOAD_ERR_NO_FILE
                   $error_detected .= "<LI>"._T("Le fichier que vous avez envoyé a une taille nulle !")."</LI>";
				   $error_detected .= "<LI>"._T("Il n'y a pas de fichier attaché.")."</LI>";
                   break;
				}
				unset($_FILES['mailing_userfile']);
			}
			else
			{
				// Everything ok: proceed and backup userfile information 
				// reminder: file is stored here: $_FILES['mailing_userfile']['tmp_name']
				$_SESSION['mailing_userfile']=$_FILES['mailing_userfile'];
				
				// Read file
				$handle=fopen($_FILES['mailing_userfile']['tmp_name'], 'rb');
				$f_contents=fread($handle, filesize($_FILES['mailing_userfile']['tmp_name']));
				//Encode The Data For Transition using base64_encode();
				$f_contents=chunk_split(base64_encode($f_contents));
				fclose($handle);

				// Boundry for marking the split & Multitype Headers
				$_SESSION['mime_boundary'] = md5(time());
				$_SESSION['attachment_headers']  = 'MIME-Version: 1.0'.$eol;
				$_SESSION['attachment_headers'] .= "Content-Type: multipart/mixed;".$eol." boundary=\"".$_SESSION['mime_boundary']."\"".$eol.$eol;
                                $_SESSION['attachment_headers'] .= "This is a multi-part message in MIME format.".$eol;

				// Attachment
				$_SESSION['attachment_body']  = "--".$_SESSION['mime_boundary'].$eol;
				if (isset($_FILES['mailing_userfile']['type']))
					$_SESSION['attachment_body'] .= "Content-Type: ".$_FILES['mailing_userfile']['type'];
				else
					$_SESSION['attachment_body'] .= "Content-Type: application/octet-stream";
				$_SESSION['attachment_body'] .= "; name=\"".$_FILES['mailing_userfile']['name']."\"".$eol;
				$_SESSION['attachment_body'] .= "Content-Transfer-Encoding: base64".$eol;
				// !! This line needs TWO end of lines !! IMPORTANT !!
				$_SESSION['attachment_body'] .= "Content-Disposition: inline; filename=\"".$_FILES['mailing_userfile']['name']."\"".$eol.$eol;
				$_SESSION['attachment_body'] .= $f_contents.$eol.$eol;
				$_SESSION['attachment_body'] .= "--".$_SESSION['mime_boundary'].$eol;
				// Text version
				$_SESSION['attachment_body'] .= "Content-Type: text/plain; charset=iso-8859-1".$eol;
				$_SESSION['attachment_body'] .= "Content-Transfer-Encoding: 8bit".$eol;
			}
		}
	}
	else if (isset($_POST["dettach_go"]))
	{
        	if (file_exists($rep.$_SESSION['mailing_userfile']['tmp_name']))
			unlink($rep.$_SESSION['mailing_userfile']['tmp_name']);
		unset($_SESSION['mailing_userfile']);
		unset($_SESSION['attachment_body']);
		unset($_SESSION['attachment_headers']);
		unset($_SESSION['mime_boundary']);
	}
	else if (isset($_POST["mailing_go"]))
	{
		if ($mailing_objet=="")
			$error_detected .= "<LI>"._T("Veuillez indiquer un objet pour le message.")."</LI>";

		if ($mailing_replyto=="")
			$error_detected .= "<LI>"._T("Veuillez remplir le champ \"Répondre A\" pour le message.")."</LI>";

		if ($mailing_corps=="")
			$error_detected .= "<LI>"._T("Veuillez saisir un message.")."</LI>";
			
		if (!isset($_POST["mailing_adh"]))
			$error_detected .= "<LI>"._T("Veuillez sélectionner au moins un adhérent.")."</LI>";

		if ($error_detected=="")
			$etape = 1;
	}	

	include("header.php");

	if ($etape==0)
	{
		
		if (isset($_GET["filtre_3"]))
			if (is_numeric($_GET["filtre_3"]))
				$_SESSION["filtre_adh_3"]=$_GET["filtre_3"];
	
		if (isset($_GET["filtre_2"]))
			if (is_numeric($_GET["filtre_2"]))
				$_SESSION["filtre_adh_2"]=$_GET["filtre_2"];
	
		if (isset($_GET["filtre"]))
			if (is_numeric($_GET["filtre"]))
				$_SESSION["filtre_adh"]=$_GET["filtre"];
	
		// Tri
	
		if (isset($_GET["tri"]))
			if (is_numeric($_GET["tri"]))
			{
				if ($_SESSION["tri_adh"]==$_GET["tri"])
					$_SESSION["tri_adh_sens"]=($_SESSION["tri_adh_sens"]+1)%2;
				else
				{
					$_SESSION["tri_adh"]=$_GET["tri"];
					$_SESSION["tri_adh_sens"]=0;
				}
			}
		
		if (isset($_GET["etiquettes"]))
		{
?> 
			<H1 class="titre"><? echo _T("Génération d'étiquettes"); ?></H1>
<?
		}
		else
		{	
?> 
			<H1 class="titre"><? echo _T("Mailing"); ?></H1>
<?
		}
		
		// Affichage des erreurs
		if ($error_detected!="")
		{
?>
		  	<DIV id="errorbox">
		  		<H1><? echo _T("- ERREUR -"); ?></H1>
		  		<UL>
		  			<? echo $error_detected; ?>
		  		</UL>
		  	</DIV>
<?
		}

		// selection des adherents et application filtre / tri
			
		$requete[0] = "SELECT id_adh, nom_adh, prenom_adh, libelle_groupe, activite_adh,
			       libelle_statut, bool_exempt_adh, titre_adh, email_adh, bool_admin_adh, date_echeance, email_pro_adh
			       FROM ".PREFIX_DB."adherents, ".PREFIX_DB."statuts, ".PREFIX_DB."groupes
			       WHERE ".PREFIX_DB."adherents.id_statut=".PREFIX_DB."statuts.id_statut
			       AND ".PREFIX_DB."adherents.id_groupe=".PREFIX_DB."groupes.id_groupe ";
		$requete[1] = "SELECT count(id_adh)
			       FROM ".PREFIX_DB."adherents
			       WHERE 1=1 ";
									
		// filtre d'affichage des adherents activés/desactivés
		if ($_SESSION["filtre_adh_2"]=="1")
		{
			$requete[0] .= "AND ".PREFIX_DB."adherents.activite_adh='1' ";
			$requete[1] .= "AND ".PREFIX_DB."adherents.activite_adh='1' ";
		}
		if ($_SESSION["filtre_adh_2"]=="2")
		{
			$requete[0] .= "AND ".PREFIX_DB."adherents.activite_adh='0' ";
			$requete[1] .= "AND ".PREFIX_DB."adherents.activite_adh='0' ";
		}

		// filtre d'affichage des adherents à jour
		if ($_SESSION["filtre_adh"]=="3")
		{
			$requete[0] .= "AND (date_echeance > ".$DB->DBDate(time())." OR bool_exempt_adh='1') ";
			$requete[1] .= "AND (date_echeance > ".$DB->DBDate(time())." OR bool_exempt_adh='1') ";
		}

		// filtre d'affichage des adherents retardataires
		if ($_SESSION["filtre_adh"]=="2")
		{
			$requete[0] .= "AND date_echeance < ".$DB->DBDate(time())." ";
			$requete[1] .= "AND date_echeance < ".$DB->DBDate(time())." ";
		}
	
		// filtre d'affichage des adherents bientot a echeance
		if ($_SESSION["filtre_adh"]=="1")
		{
			$requete[0] .= "AND date_echeance > ".$DB->DBDate(time())."
				        AND date_echeance < ".$DB->OffsetDate(30)." ";
			$requete[1] .= "AND date_echeance > ".$DB->DBDate(time())."
				        AND date_echeance < ".$DB->OffsetDate(30)." ";
		}
		
		// filtre d'affichage par groupe
		if ($_SESSION["filtre_adh_3"] > 0)
		{
			$requete[0] .= "AND (".PREFIX_DB."adherents.id_groupe=".$_SESSION["filtre_adh_3"]." ";
			$requete[0] .= "OR ".PREFIX_DB."adherents.referent=".$_SESSION["filtre_adh_3"].") ";
			$requete[1] .= "AND (".PREFIX_DB."adherents.id_groupe=".$_SESSION["filtre_adh_3"]." ";
			$requete[1] .= "OR ".PREFIX_DB."adherents.referent=".$_SESSION["filtre_adh_3"].") ";
		}
                else if ($_SESSION["filtre_adh_3"] == -1) // affichage des référents (pseudo groupe virtuel :-)
                {
			$requete[0] .= "AND ".PREFIX_DB."adherents.referent>'0'";
			$requete[1] .= "AND ".PREFIX_DB."adherents.referent>'0'";
                }

		// phase de tri	
		
		if ($_SESSION["tri_adh_sens"]=="0")
			$tri_adh_sens_txt="ASC";
		else
			$tri_adh_sens_txt="DESC";
	
		$requete[0] .= "ORDER BY ";
		
		// tri par groupe 
		if ($_SESSION["tri_adh"]=="1")
			$requete[0] .= "libelle_groupe ".$tri_adh_sens_txt.",";
			
		// tri par statut
		elseif ($_SESSION["tri_adh"]=="2")
			$requete[0] .= "priorite_statut ".$tri_adh_sens_txt.",";
	
		// tri par echeance
		elseif ($_SESSION["tri_adh"]=="3")
			$requete[0] .= "bool_exempt_adh ".$tri_adh_sens_txt.", date_echeance ".$tri_adh_sens_txt.",";
	
		// defaut : tri par nom, prenom
		$requete[0] .= "nom_adh ".$tri_adh_sens_txt.", prenom_adh ".$tri_adh_sens_txt; 
		
		$resultat = &$DB->Execute($requete[0]);
		$nbadh = &$DB->Execute($requete[1]);
?>
		<SCRIPT LANGUAGE="JavaScript">
		<!--
		var checked = 1; 	
		function check()
		{ 
			for (var i=0;i<document.mailing_form.elements.length;i++)
			{
				var e = document.mailing_form.elements[i];
				if(e.type == "checkbox")
				{
					e.checked = checked;
				}
			}
			checked = !checked;
		}
		-->
		</SCRIPT>
		<TABLE id="infoline" width="100%">
			<TR>
				<TD class="left"><? echo $nbadh->fields[0]." "; if ($nbadh->fields[0]!=1) echo _T("adhérents"); else echo _T("adhérent"); ?></TD>
				<TD class="right">
					<DIV id="listfilter">
						<FORM action="mailing_adherents.php" method="get" name="filtre">
						 	<? echo _T("Afficher :"); ?>&nbsp;
							<SELECT name="filtre" onChange="form.submit()">
								<OPTION value="0"<? isSelected("0",$_SESSION["filtre_adh"]) ?>><? echo _T("Tous les adhérents"); ?></OPTION>
								<OPTION value="3"<? isSelected("3",$_SESSION["filtre_adh"]) ?>><? echo _T("Les adhérents à jour"); ?></OPTION>
								<OPTION value="1"<? isSelected("1",$_SESSION["filtre_adh"]) ?>><? echo _T("Les échéances proches"); ?></OPTION>
								<OPTION value="2"<? isSelected("2",$_SESSION["filtre_adh"]) ?>><? echo _T("Les retardataires"); ?></OPTION>
							</SELECT>
							<SELECT name="filtre_2" onChange="form.submit()">
								<OPTION value="0"<? isSelected("0",$_SESSION["filtre_adh_2"]) ?>><? echo _T("Tous  les comptes"); ?></OPTION>
								<OPTION value="1"<? isSelected("1",$_SESSION["filtre_adh_2"]) ?>><? echo _T("Comptes actifs"); ?></OPTION>
								<OPTION value="2"<? isSelected("2",$_SESSION["filtre_adh_2"]) ?>><? echo _T("Comptes désactivés"); ?></OPTION>
							</SELECT>
							<SELECT name="filtre_3" onChange="form.submit()">
								<? if ( (!$apply_admin_policy) ||
								        (($apply_admin_policy)&&($_SESSION["admin_status"]>=FULL_ADMIN))
									  )
								{
								?>
								<OPTION value="-1"<? isSelected("-1",$_SESSION["filtre_adh_3"]) ?>><? echo _T("Référents"); ?></OPTION>
								<OPTION value="0"<? isSelected("0",$_SESSION["filtre_adh_3"]) ?>><? echo _T("Tous les groupes"); ?></OPTION>
								<?
								}
									$requete = "SELECT * FROM ".PREFIX_DB."groupes";
									if (($apply_admin_policy)&&($_SESSION["admin_status"]<FULL_ADMIN))
										$requete .= " WHERE ".PREFIX_DB."groupes.id_groupe=".$_SESSION["admin_status"];
									$result = &$DB->Execute($requete);
									while (!$result->EOF)
									{									
								?>
									<OPTION value="<? echo $result->fields["id_groupe"] ?>"<? isSelected($result->fields["id_groupe"],$_SESSION["filtre_adh_3"]) ?>><? echo _T($result->fields["libelle_groupe"]); ?></OPTION>
								<?
										$result->MoveNext();
									}
									$result->Close();
								?>
							</SELECT>
							<INPUT type="submit" value="<? echo _T("Filtrer"); ?>">
						</FORM>
					</DIV>
				</TD>
			</TR>
		</TABLE>
<?
		if (isset($_GET["etiquettes"]))
		{
?>
						<FORM action="etiquettes_adherents.php" method="post" name="mailing_form" target="_blank">
<?
		}
		else
		{
?>
						<FORM action="mailing_adherents.php" method="post" enctype="multipart/form-data" name="mailing_form">
<?
		}
?>
						<table width="100%"> 
							<TR> 
							<TH class="listing" width="15">#</TH> 
				  			<TH class="listing left" width="250"> 
									<A href="mailing_adherents.php?tri=0" class="listing"><? echo _T("Nom"); ?></A>
									<?
										if ($_SESSION["tri_adh"]=="0")
											if ($_SESSION["tri_adh_sens"]=="0")
												echo "<IMG src=\"images/asc.png\" width=\"7\" height=\"7\" alt=\"\">";
											else 
												echo "<IMG src=\"images/desc.png\" width=\"7\" height=\"7\" alt=\"\">";
									?>
								</TH> 
								<TH class="listing left"> 
									<A href="mailing_adherents.php?tri=1" class="listing"><? echo _T("E-Mail Perso"); ?></A>
									<?
										if ($_SESSION["tri_adh"]=="1")
											if ($_SESSION["tri_adh_sens"]=="0")
												echo "<IMG src=\"images/asc.png\" width=\"7\" height=\"7\" alt=\"\">";
											else 
												echo "<IMG src=\"images/desc.png\" width=\"7\" height=\"7\" alt=\"\">";
									?>
								</TH> 
								<TH class="listing left"> 
									<A href="mailing_adherents.php?tri=4" class="listing"><? echo _T("E-Mail Pro"); ?></A>
									<?
										if ($_SESSION["tri_adh"]=="4")
											if ($_SESSION["tri_adh_sens"]=="0")
												echo "<IMG src=\"images/asc.png\" width=\"7\" height=\"7\" alt=\"\">";
											else 
												echo "<IMG src=\"images/desc.png\" width=\"7\" height=\"7\" alt=\"\">";
									?>
								</TH> 
								<TH class="listing left"> 
									<A href="mailing_adherents.php?tri=2" class="listing"><? echo _T("Statut"); ?></A>
									<?
										if ($_SESSION["tri_adh"]=="2")
											if ($_SESSION["tri_adh_sens"]=="0")
												echo "<IMG src=\"images/asc.png\" width=\"7\" height=\"7\" alt=\"\">";
											else 
												echo "<IMG src=\"images/desc.png\" width=\"7\" height=\"7\" alt=\"\">";
									?>
								</TH> 
								<TH class="listing left"> 
									<A href="mailing_adherents.php?tri=3" class="listing"><? echo _T("Etat cotisations"); ?></A>
									<?
										if ($_SESSION["tri_adh"]=="3")
											if ($_SESSION["tri_adh_sens"]=="0")
												echo "<IMG src=\"images/asc.png\" width=\"7\" height=\"7\" alt=\"\">";
											else 
												echo "<IMG src=\"images/desc.png\" width=\"7\" height=\"7\" alt=\"\">";
									?>
								</TH> 
								<TH width="55" class="listing">Actions</TH> 
							</tr> 
<? 
		if ($resultat->EOF)
		{
?>	
							<tr>
								<td colspan="6" class="emptylist"><? echo _T("aucun adhérent"); ?></td>
							</tr>
<?
		}
		else while (!$resultat->EOF) 
		{ 
		// définition CSS pour adherent désactivé
		if ($resultat->fields[4]=="1")
			$row_class = "actif";
		else
			$row_class = "inactif";
			
		// temps d'adhésion
		if($resultat->fields[6])
		{
			$statut_cotis = _T("Exempt de cotisation");
			$row_class .= " cotis-exempt";
		}
		else
		{
			if ($resultat->fields[10]=="")
			{
				$statut_cotis = _T("N'a jamais cotisé");
				$row_class .= " cotis-never";
			}
			else
			{
				$date_fin = split("-",$resultat->fields[10]);
				$ts_date_fin = mktime(0,0,0,$date_fin[1],$date_fin[2],$date_fin[0]);
				$aujourdhui = time();
				
				$difference = intval(($ts_date_fin - $aujourdhui)/(3600*24));
				if ($difference==0)
				{
					$statut_cotis = _T("Dernier jour !");
					$row_class .= " cotis-lastday";
				}
				elseif ($difference<0)
				{
					$statut_cotis = _T("En retard de ").-$difference." "._T("jours")." ("._T("depuis le")." ".$date_fin[2]."/".$date_fin[1]."/".$date_fin[0].")";
					$row_class .= " cotis-late";
				}
				else
				{
					$statut_cotis = $difference." "._T("jours restants")." ("._T("fin le")." ".$date_fin[2]."/".$date_fin[1]."/".$date_fin[0].")";
					if ($difference < 30)
						$row_class .= " cotis-soon";
					else
						$row_class .= " cotis-ok";	
				}				
			}
		}
?>							 
							<TR> 
								<TD width="15" class="<? echo $row_class; ?>" nowrap> 
									<INPUT type="checkbox" name="mailing_adh[]" value="<? echo $resultat->fields[0] ?>" <? if (in_array($resultat->fields[0],$mailing_adh)) echo "CHECKED"; ?>> 
								</TD> 
								<TD class="<? echo $row_class; ?>" nowrap>
<?
			if ($resultat->fields[7]=="1") {
?>
									<IMG src="images/icon-male.png" Alt="<? echo _T("[H]"); ?>" align="middle" width="10" height="12">
<?
			} else {
?>
									<IMG src="images/icon-female.png" Alt="<? echo _T("[F]"); ?>" align="middle" width="9" height="12">
<?
			}
?>
<?			if ($resultat->fields[9] >= FULL_ADMIN) {
?>
				<img src="images/icon-red-star.png" Alt="<? echo _T("[admin]"); ?>" align="middle" width="12" height="13">
<?
			} elseif ($resultat->fields[9] > 0) {
?>
				<img src="images/icon-star.png" Alt="<? echo _T("[admin]"); ?>" align="middle" width="12" height="13">
<?
			} else {
?>
				<img src="images/icon-empty.png" Alt="" align="middle" width="12" height="13">
<?
			}
?>

									<A href="voir_adherent.php?id_adh=<? echo $resultat->fields["id_adh"] ?>"><? echo htmlentities(strtoupper($resultat->fields[1]), ENT_QUOTES)." ".htmlentities($resultat->fields[2], ENT_QUOTES); ?></A>
								</TD> 
								<TD class="<? echo $row_class; ?>" nowrap> 
									<? if ($resultat->fields[8]!="") echo "<A href=\"mailto:".htmlentities($resultat->fields[8], ENT_QUOTES)."\">".htmlentities($resultat->fields[8], ENT_QUOTES)."</A>"; ?>&nbsp; 
								</TD> 
								<TD class="<? echo $row_class; ?>" nowrap> 
									<? if ($resultat->fields[11]!="") echo "<A href=\"mailto:".htmlentities($resultat->fields[11], ENT_QUOTES)."\">".htmlentities($resultat->fields[11], ENT_QUOTES)."</A>"; ?>&nbsp; 
								</TD> 
								<TD class="<? echo $row_class; ?>" nowrap><? echo _T($resultat->fields[5]) ?></TD> 
								<TD class="<? echo $row_class; ?>" nowrap><? echo $statut_cotis ?></TD>
								<TD width="55" class="<? echo $row_class; ?> center"> 
									<A href="ajouter_adherent.php?id_adh=<? echo $resultat->fields[0] ?>"><IMG src="images/icon-edit.png" alt="<? echo _T("[mod]"); ?>" border="0" width="12" height="13"></A>
									<A href="gestion_contributions.php?id_adh=<? echo $resultat->fields[0] ?>"><IMG src="images/icon-money.png" alt="<? echo _T("[$]"); ?>" border="0" width="13" height="13"></A>
									<A onClick="return confirm('<? echo str_replace("\n","\\n",addslashes(_T("Voulez-vous vraiment supprimer cet adhérent de la base, ceci supprimera aussi l'historique de ses cotisations. Pour éviter cela vous pouvez simplement désactiver le compte.\n\nVoulez-vous tout de même supprimer cet adhérent ?"))); ?>')" href="gestion_adherents.php?sup=<? echo $resultat->fields[0] ?>"><IMG src="images/icon-trash.png" alt="<? echo _T("[sup]"); ?>" border="0" width="11" height="13"></A>
								</TD> 
							</TR> 
<? 
			$resultat->MoveNext();
		} 
		$resultat->Close();
?>							 
						</TABLE>
						<A href="#" onClick="check()"><? echo _T("[ Tout cocher / décocher ]"); ?></A>
						<BR>
						<BR>
<?
		if (isset($_GET["etiquettes"]))
		{
?>
							<DIV align="center"><INPUT type="submit" value="<? echo _T("Génération d'étiquettes"); ?>"></DIV>
<?
		}
		else
		{
?>
						<DIV align="center">
						<TABLE border="0" id="input-table">
							<TR>
								<TH id="libelle"><? echo _T("De :"); ?></TH>
							</TR>
							<TR>
								<TD><INPUT type="text" name="mailing_from" readonly value="<? echo htmlentities(PREF_EMAIL_NOM." <".PREF_EMAIL.">", ENT_QUOTES); ?>" size="80"></TD>
							</TR>
							<TR>
								<TH id="libelle"><? echo _T("Répondre A :"); ?></TH>
							</TR>
							<TR>
								<TD><INPUT type="text" name="mailing_replyto" value="<? echo htmlentities($mailing_replyto, ENT_QUOTES); ?>" size="80"></TD>
							</TR>
							<TR>
								<TH id="libelle"><? echo _T("Objet :"); ?></TH>
							</TR>
							<TR>
								<TD><INPUT type="text" name="mailing_objet" value="<? echo htmlentities($mailing_objet, ENT_QUOTES); ?>" size="80"></TD>
							</TR>

							<TR>
								<TH id="libelle"><? echo _T("Fichier Attaché :"); ?></TH>
							</TR>
							<TR>
								<TD>
										<? if (isset($_SESSION['mailing_userfile'])) {
											echo $_SESSION['mailing_userfile']['name']." (";
											if (isset($_SESSION['mailing_userfile']['type']))
												echo "type: ".$_SESSION['mailing_userfile']['type'].", ";
											echo "taille: ".$_SESSION['mailing_userfile']['size']." octets)";
										?>	<INPUT TYPE=SUBMIT name="dettach_go" value="Supprimer">
										<? } else { ?>
										<INPUT TYPE=HIDDEN NAME=MAX_FILE_SIZE VALUE=<? echo $MFS;?>>
										<INPUT TYPE=FILE NAME="mailing_userfile" size="80">
										<INPUT TYPE=SUBMIT name="attach_go" value="Attacher">
										<? } ?>
								</TD>

							</TR>

							<TR>
								<TH id="libelle"><? echo _T("Message :"); ?></TH>
							</TR>
							<TR>
								<TD><TEXTAREA name="mailing_corps" cols="72" rows="15"><? echo htmlentities($mailing_corps, ENT_QUOTES); ?></TEXTAREA></TD>
							</TR>
							<TR>
								<TH align="center">
									<BR>
									<INPUT type="submit" value="<? echo _T("Prévisualiser"); ?>">
								</TH>
							</TR>
						</TABLE>
						</DIV>
<?
		}
?>
						<INPUT type="hidden" name="mailing_go" value="1">
						</FORM>
<? 
	}
	else
	{
		$confirm_detected="";
		
		// $mailing_corps = $_POST["mailing_corps"];
		// adhérents avec email
		$requete = "SELECT id_adh, nom_adh, prenom_adh, pseudo_adh, activite_adh,
				libelle_statut, bool_exempt_adh, titre_adh, email_adh, bool_admin_adh, date_echeance, email_pro_adh
				FROM ".PREFIX_DB."adherents, ".PREFIX_DB."statuts
	  				WHERE ".PREFIX_DB."adherents.id_statut=".PREFIX_DB."statuts.id_statut AND (";
		$where_clause = "";
		while(list($key,$value)=each($mailing_adh))
		{
			if ($where_clause!="")
				$where_clause .= " OR ";
			$where_clause .= "id_adh='".$value."'";
		}
		$requete .= $where_clause.") AND (email_adh IS NOT NULL OR email_pro_adh IS NOT NULL) ORDER by nom_adh, prenom_adh;";
		// echo $requete;
		$resultat = &$DB->Execute($requete);
		if (isset($_POST["mailing_confirmed"]))
			$confirm_detected = _T("Pensez à contacter les adhérents ne disposant pas d'une adresse E-Mail par un autre moyen.");
?>
			<H1 class="titre"><? echo _T("Mailing"); ?> <? if (isset($_POST["mailing_confirmed"])) echo _T("effectué !"); else echo _T("(prévisualisation)"); ?></H1>
<?
		// Affichage des erreurs
		if ($confirm_detected!="")
	  	echo "			<BR><DIV align=\"center\"><TABLE><TR><TD style=\"background: #DDFFDD; color: #FF0000\"><B><DIV align=\"center\">"._T("- ATTENTION -")."</DIV></B>" . $confirm_detected . "</TD></TR></TABLE></DIV>";
?>
			<BR>
			<B><? echo _T("Destinataires du mailing :"); ?></B>
			<TABLE width="100%"> 
				<TR> 
	  				<TH class="listing left" width="250"><? echo _T("Nom"); ?></TH> 
					<TH class="listing left"><? echo _T("E-Mail"); ?></TH> 
					<TH class="listing left"><? echo _T("E-Mail Pro"); ?></TH> 
					<TH class="listing left"> <? echo _T("Statut"); ?></TH> 
					<TH class="listing left"><? echo _T("Etat cotisations"); ?></TH> 
				</TR> 			
<?		
		$num_mails = 0;
		$concatmail = "";
		if (isset($_SESSION['attachment_body']))
			$full_mailing_corps = $_SESSION['attachment_body'].$mailing_corps.$eol.$eol."--".$_SESSION['mime_boundary']."--".$eol.$eol;
		else
			$full_mailing_corps = $mailing_corps;

		if (isset($_SESSION['attachment_headers']))
			$mailing_headers .= $_SESSION['attachment_headers'];
		
		if ($resultat->EOF)
		{
?>	
				<tr>
					<td colspan="4" bgcolor="#EEEEEE" align="center"><i><? echo _T("aucun adhérent"); ?></i></td>
				</tr>
<?
		}
		else while (!$resultat->EOF) 
		{
			if (isset($_POST["mailing_confirmed"]))
			{
				if ($resultat->fields[8]!="")
				{
					mail ($resultat->fields[8], $mailing_objet, $full_mailing_corps, $mailing_headers);
					$num_mails++;
					$concatmail = $concatmail . " " . $resultat->fields[8];
				}
				if ($resultat->fields[11]!="")
				{
					mail ($resultat->fields[11], $mailing_objet, $full_mailing_corps, $mailing_headers);
					$concatmail = $concatmail . " " . $resultat->fields[11];
					$num_mails++;
				}
			}		

			// définition CSS pour adherent désactivé
			if ($resultat->fields[4]=="1")
				$activity_class = "";
			else
				$activity_class = " class=\"inactif\"";
				
			// temps d'adhésion
			if($resultat->fields[6])
			{
				$statut_cotis = _T("Exempt de cotisation");
				$color = "#DDFFDD";
			}
			else
			{
				if ($resultat->fields[10]=="")
				{
					$statut_cotis = _T("N'a jamais cotisé");
					$color = "#EEEEEE";			
				}
				else
				{
					$date_fin = split("-",$resultat->fields[10]);
					$ts_date_fin = mktime(0,0,0,$date_fin[1],$date_fin[2],$date_fin[0]);
					$aujourdhui = time();
					
					$difference = intval(($ts_date_fin - $aujourdhui)/(3600*24));
					if ($difference==0)
					{
						$statut_cotis = _T("Dernier jour !");
						$color = "#FFDDDD";
					}
					elseif ($difference<0)
					{
						$statut_cotis = _T("En retard de ").-$difference." "._T("jours")." ("._T("depuis le")." ".$date_fin[2]."/".$date_fin[1]."/".$date_fin[0].")";
						$color = "#FFDDDD";
					}
					else
					{
						$statut_cotis = $difference." "._T("jours restants")." ("._T("fin le")." ".$date_fin[2]."/".$date_fin[1]."/".$date_fin[0].")";
						if ($difference < 30)
							$color = "#FFE9AB";
						else
							$color = "#DDFFDD";	
					}					
				}
			}
		
?>							 
				<tr> 
					<td bgcolor="<? echo $color ?>"<? echo $activity_class ?>>
<?
			if ($resultat->fields[7]=="1") {
?>
						<img src="images/icon-male.png" Alt="<? echo _T("[H]"); ?>" align="middle" width="10" height="12">
<?
			} else {
?>
						<img src="images/icon-female.png" Alt="<? echo _T("[F]"); ?>" align="middle" width="9" height="12">
<?
			}
?>
<?
			if ($resultat->fields[9] >= FULL_ADMIN) {
?>
						<img src="images/icon-red-star.png" Alt="<? echo _T("[admin]"); ?>" align="middle" width="12" height="13">
<?
			} elseif ($resultat->fields[9] > 0) {
?>
						<img src="images/icon-star.png" Alt="<? echo _T("[admin]"); ?>" align="middle" width="12" height="13">
<?
			} else {
?>
						<img src="images/icon-empty.png" Alt="" align="middle" width="12" height="13">
<?
			}
?>
						<a href="voir_adherent.php?id_adh=<? echo $resultat->fields["id_adh"] ?>"><? echo htmlentities(strtoupper($resultat->fields[1]), ENT_QUOTES)." ".htmlentities($resultat->fields[2], ENT_QUOTES) ?></a>
					</td> 
					<td bgcolor="<? echo $color ?>"<? echo $activity_class ?>> 
						<? if ($resultat->fields[8]!="") echo "<A href=\"mailto:".htmlentities($resultat->fields[8], ENT_QUOTES)."\">".htmlentities($resultat->fields[8], ENT_QUOTES)."</A>"; ?>&nbsp; 
					</td> 
					<td bgcolor="<? echo $color ?>"<? echo $activity_class ?>> 
						<? if ($resultat->fields[11]!="") echo "<A href=\"mailto:".htmlentities($resultat->fields[11], ENT_QUOTES)."\">".htmlentities($resultat->fields[11], ENT_QUOTES)."</A>"; ?>&nbsp; 
					</td> 
					<td bgcolor="<? echo $color ?>"<? echo $activity_class ?>>
						<? echo _T($resultat->fields[5]) ?> 
					</td> 
					<td bgcolor="<? echo $color ?>"<? echo $activity_class ?>> 
						<? echo $statut_cotis ?>
					</td>
				</TR>

<?	
			$resultat->MoveNext();
		}
		
		if (isset($_POST["mailing_confirmed"]))
		{
			dblog(_T("Envoi d'un mailing intitulé :")." \"".$mailing_objet."\" - Reply-To: ".$mailing_replyto." - ".$num_mails." "._T("destinataires"), $concatmail."\n".$mailing_corps);
        		if (file_exists($rep.$_SESSION['mailing_userfile']['tmp_name']))
				unlink($rep.$_SESSION['mailing_userfile']['tmp_name']);
			unset($_SESSION['mailing_userfile']);
			unset($_SESSION['attachment_body']);
			unset($_SESSION['attachment_headers']);
			unset($_SESSION['mime_boundary']);
		}

		$resultat->Close();
?>
			</TABLE>
			<DIV id="mailing_preview" align="center">
				<TABLE border="0">
				<TR><TH><? echo _T("De :"); ?></TH></TR>
				<TR><TD><? echo htmlentities($PREF_EMAIL_NOM." <".PREF_EMAIL.">", ENT_QUOTES); ?></TD></TR>
				<TR><TH><? echo _T("Répondre A :"); ?></TH></TR>
				<TR><TD><? echo htmlentities($mailing_replyto, ENT_QUOTES); ?></TD></TR>
				<TR><TH><? echo _T("Objet :"); ?></TH></TR>
				<TR><TD><? echo htmlentities($mailing_objet, ENT_QUOTES); ?></TD></TR>
				<? if (isset($_SESSION['mailing_userfile'])) { ?>
				<TR><TH><? echo _T("Fichier Attaché :"); ?></TH></TR>
				<TR><TD>
					<?
					echo $_SESSION['mailing_userfile']['name']." (";
					if (isset($_SESSION['mailing_userfile']['type']))
						echo "type: ".$_SESSION['mailing_userfile']['type'].", ";
					echo "taille: ".$_SESSION['mailing_userfile']['size']." octets)";
					?>
				</TD></TR>
				<? } ?>
				<TR><TH><? echo _T("Message :"); ?></TH></TR>
				<TR><TD><? echo nl2br(htmlentities($mailing_corps, ENT_QUOTES)); ?></TD></TR>
				</TABLE>
			</DIV>
						<DIV align="center">
						<TABLE>
							<TR>
<?
		if (!isset($_POST["mailing_confirmed"]))
		{
?>
								<TD>
									<FORM action="mailing_adherents.php" method="post">
<?
			reset($mailing_adh);
			while(list($key,$value)=each($mailing_adh))
			{
				echo "<INPUT type=\"hidden\" name=\"mailing_adh[]\" value=\"".$value."\">";
			}
?>
										<INPUT type ="hidden" name="mailing_from" value="<? echo htmlentities($PREF_EMAIL_NOM." <".PREF_EMAIL.">", ENT_QUOTES); ?>">
										<INPUT type ="hidden" name="mailing_replyto" value="<? echo htmlentities($mailing_replyto, ENT_QUOTES); ?>">
										<INPUT type ="hidden" name="mailing_corps" value="<? echo htmlentities($mailing_corps, ENT_QUOTES); ?>">
										<INPUT type ="hidden" name="mailing_objet" value="<? echo htmlentities($mailing_objet, ENT_QUOTES); ?>">
										<INPUT type="submit" value="<? echo _T("Retour"); ?>">&nbsp;&nbsp;&nbsp;
									</FORM>
								</TD>
								<TD>
									<FORM action="mailing_adherents.php" method="post">
<?
			reset($mailing_adh);
			while(list($key,$value)=each($mailing_adh))
			{
				echo "<INPUT type=\"hidden\" name=\"mailing_adh[]\" value=\"".$value."\">";
			}
?>
										<INPUT type ="hidden" name="mailing_from" value="<? echo htmlentities($PREF_EMAIL_NOM." <".PREF_EMAIL.">", ENT_QUOTES); ?>">
										<INPUT type ="hidden" name="mailing_replyto" value="<? echo htmlentities($mailing_replyto, ENT_QUOTES); ?>">
										<INPUT type ="hidden" name="mailing_corps" value="<? echo htmlentities($mailing_corps, ENT_QUOTES); ?>">
										<INPUT type ="hidden" name="mailing_objet" value="<? echo htmlentities($mailing_objet, ENT_QUOTES); ?>">
										<INPUT type ="hidden" name="mailing_confirmed" value="1">
										<INPUT type ="hidden" name="mailing_go" value="1">
										&nbsp;&nbsp;&nbsp;<INPUT type="submit" value="<? echo _T("Envoyer"); ?>">
									</FORM>
								</TD>
<?
		}
		else
		{
?>
								<TD>
									<FORM action="gestion_adherents.php" method="post">
										<INPUT type="submit" value="<? echo _T("Retour"); ?>">
									</FORM>
								</TD>
<?
		}
?>								
							<TR>
						</TABLE>
						</DIV>
						<BR>
			<B><? echo _T("Adhérents non joignables par email :"); ?></B>
			<TABLE width="100%"> 
				<TR> 
	  				<TH class="listing left" width="250"><? echo _T("Nom"); ?></TH> 
					<TH class="listing left"><? echo _T("Coordonnées"); ?></TH> 
					<TH class="listing left"> <? echo _T("Statut"); ?></TH> 
					<TH class="listing left"><? echo _T("Etat cotisations"); ?></TH> 
				</TR> 			
<?
		// adhérents sans email
		$requete = "SELECT id_adh, nom_adh, prenom_adh, adresse_adh, activite_adh,
				libelle_statut, bool_exempt_adh, titre_adh, cp_adh, bool_admin_adh, date_echeance,
				ville_adh, tel_adh, gsm_adh, msn_adh, icq_adh, pays_adh, jabber_adh, adresse2_adh
				FROM ".PREFIX_DB."adherents, ".PREFIX_DB."statuts
			       	WHERE ".PREFIX_DB."adherents.id_statut=".PREFIX_DB."statuts.id_statut AND (";
		$requete .= $where_clause.") AND (email_adh IS NULL AND email_pro_adh IS NULL) ORDER by nom_adh, prenom_adh;";
		// echo $requete;
		$resultat = &$DB->Execute($requete);

		if ($resultat->EOF)
		{
?>	
							<tr>
								<td colspan="4" bgcolor="#EEEEEE" align="center"><i><? echo _T("aucun adhérent"); ?></i></td>
							</tr>
<?
		}
		else while (!$resultat->EOF) 
			{
				// définition CSS pour adherent désactivé
				if ($resultat->fields[4]=="1")
					$activity_class = "";
				else
					$activity_class = " class=\"inactif\"";
					
				// temps d'adhésion
				if($resultat->fields[6])
				{
					$statut_cotis = _T("Exempt de cotisation");
					$color = "#DDFFDD";
				}
				else
				{
					if ($resultat->fields[10]=="")
					{
						$statut_cotis = _T("N'a jamais cotisé");
						$color = "#EEEEEE";			
					}
					else
					{
						$date_fin = split("-",$resultat->fields[10]);
						$ts_date_fin = mktime(0,0,0,$date_fin[1],$date_fin[2],$date_fin[0]);
						$aujourdhui = time();
						
						$difference = intval(($ts_date_fin - $aujourdhui)/(3600*24));
						if ($difference==0)
						{
							$statut_cotis = _T("Dernier jour !");
							$color = "#FFDDDD";
						}
						elseif ($difference<0)
						{
							$statut_cotis = _T("En retard de ").-$difference." "._T("jours")." ("._T("depuis le")." ".$date_fin[2]."/".$date_fin[1]."/".$date_fin[0].")";
							$color = "#FFDDDD";
						}
						else
						{
							$statut_cotis = $difference." "._T("jours restants")." ("._T("fin le")." ".$date_fin[2]."/".$date_fin[1]."/".$date_fin[0].")";
							if ($difference < 30)
								$color = "#FFE9AB";
							else
								$color = "#DDFFDD";	
						}					
					}
				}
			
?>							 
							<tr> 
								<td valign="top" bgcolor="<? echo $color ?>"<? echo $activity_class ?>>
<?
				if ($resultat->fields[7]=="1") {
?>
									<img src="images/icon-male.png" Alt="<? echo _T("[H]"); ?>" align="middle" width="10" height="12">
<?
				} else {
?>
									<img src="images/icon-female.png" Alt="<? echo _T("[F]"); ?>" align="middle" width="9" height="12">
<?
				}
?>
<?
				if ($resultat->fields[9] >= FULL_ADMIN) {
?>
									<img src="images/icon-red-star.png" Alt="<? echo _T("[admin]"); ?>" align="middle" width="12" height="13">
<?
				} elseif ($resultat->fields[9] > 0) {
?>
									<img src="images/icon-star.png" Alt="<? echo _T("[admin]"); ?>" align="middle" width="12" height="13">
<?
				} else {
?>
									<img src="images/icon-empty.png" Alt="" align="middle" width="12" height="13">
<?
				}
?>
									<a href="voir_adherent.php?id_adh=<? echo $resultat->fields["id_adh"] ?>"><? echo htmlentities(strtoupper($resultat->fields[1]), ENT_QUOTES)." ".htmlentities($resultat->fields[2], ENT_QUOTES); ?></a>
								</td> 
<?
				$coord_adh = "<table width=\"100%\" cellspacing=\"0\" cellpadding=\"0\">";
				$adresse_adh = "";
				if ($resultat->fields[3]!="")
					$adresse_adh .= htmlentities($resultat->fields[3], ENT_QUOTES);
				if ($resultat->fields[8]!="") 
				{	
					if ($adresse_adh!="")
						$adresse_adh .= "<BR>";
					$adresse_adh .= htmlentities($resultat->fields[8], ENT_QUOTES);
				}
				if ($resultat->fields[11]!="") 
				{	
					if ($adresse_adh!="")
						$adresse_adh .= "<BR>";
					$adresse_adh .= htmlentities($resultat->fields[11], ENT_QUOTES);
				}
				if ($resultat->fields[16]!="") 
				{	
					if ($adresse_adh!="")
						$adresse_adh .= "<BR>";
					$adresse_adh .= htmlentities($resultat->fields[16], ENT_QUOTES);
				}
				if ($resultat->fields[18]!="") 
				{	
					if ($adresse_adh!="")
						$adresse_adh .= "<BR>";
					$adresse_adh .= htmlentities($resultat->fields[18], ENT_QUOTES);
				}
				if ($adresse_adh!="")
					$coord_adh .= "<tr><td width=\"10\" valign=\"top\"><B>".str_replace(" ","&nbsp;",_T("Adresse :"))."</B>&nbsp;</td><td>".$adresse_adh."</td></tr>";
				if ($resultat->fields[12]!="") 
					$coord_adh .= "<tr><td style=\"padding-right: 1px;\"><B>".str_replace(" ","&nbsp;",_T("Tel :"))."</B>&nbsp;</td><td>".htmlentities($resultat->fields[12], ENT_QUOTES)."</td></tr>";
				if ($resultat->fields[13]!="") 
					$coord_adh .= "<tr><td><B>".str_replace(" ","&nbsp;",_T("GSM :"))."</B>&nbsp;</td><td>".htmlentities($resultat->fields[13], ENT_QUOTES)."</td></tr>";
				if ($resultat->fields[15]!="") 
					$coord_adh .= "<tr><td><B>".str_replace(" ","&nbsp;",_T("ICQ :"))."</B>&nbsp;</td><td>".htmlentities($resultat->fields[15], ENT_QUOTES)."</td></tr>";
				if ($resultat->fields[17]!="") 
					$coord_adh .= "<tr><td><B>".str_replace(" ","&nbsp;",_T("Jabber :"))."</B>&nbsp;</td><td>".htmlentities($resultat->fields[17], ENT_QUOTES)."</td></tr>";
				if ($resultat->fields[14]!="") 
					$coord_adh .= "<tr><td><B>".str_replace(" ","&nbsp;",_T("MSN :"))."</B>&nbsp;</td><td>".htmlentities($resultat->fields[14], ENT_QUOTES)."</td></tr>";
				$coord_adh .= "</table>";
?>
								<td valign="top" bgcolor="<? echo $color ?>"<? echo $activity_class ?>"><? echo $coord_adh; ?></td> 
								<td valign="top" bgcolor="<? echo $color ?>"<? echo $activity_class ?>><? echo _T($resultat->fields[5]) ?></td> 
								<td valign="top" bgcolor="<? echo $color ?>"<? echo $activity_class ?>><? echo $statut_cotis ?></td>
							</TR>

<?	
				$nomail_adh[]=$resultat->fields[0];
				$resultat->MoveNext();
			} 
			$resultat->Close();
			

?>
						</TABLE>
						<BR>
						<DIV align="center">
						<TABLE>
							<TR>
<?
		if (!isset($_POST["mailing_confirmed"]))
		{
?>
								<TD>
									<FORM action="mailing_adherents.php" method="post">
<?
			reset($mailing_adh);
			while(list($key,$value)=each($mailing_adh))
			{
				echo "<INPUT type=\"hidden\" name=\"mailing_adh[]\" value=\"".$value."\">";
			}
?>
										<INPUT type ="hidden" name="mailing_from" value="<? echo htmlentities($PREF_EMAIL_NOM." <".PREF_EMAIL.">", ENT_QUOTES); ?>">
										<INPUT type ="hidden" name="mailing_replyto" value="<? echo htmlentities($mailing_replyto, ENT_QUOTES); ?>">
										<INPUT type ="hidden" name="mailing_corps" value="<? echo htmlentities($mailing_corps, ENT_QUOTES); ?>">
										<INPUT type ="hidden" name="mailing_objet" value="<? echo htmlentities($mailing_objet, ENT_QUOTES); ?>">
										<INPUT type="submit" value="<? echo _T("Retour"); ?>">&nbsp;&nbsp;&nbsp;
									</FORM>
								</TD>
<?
		}
		else
		{
?>
								<TD>
									<FORM action="gestion_adherents.php" method="post">
										<INPUT type="submit" value="<? echo _T("Retour"); ?>">
									</FORM>
								</TD>
<?
		}
?>								
								<TD>
									<FORM action="etiquettes_adherents.php" method="post" target="_blank">
<?
		reset($nomail_adh);
		while(list($key,$value)=each($nomail_adh))
		{
			echo "<INPUT type=\"hidden\" name=\"mailing_adh[]\" value=\"".$value."\">";
		}
?>
										&nbsp;&nbsp;&nbsp;<INPUT type="submit" value="<? echo _T("Génération d'étiquettes"); ?>">
									</FORM>
								</TD>
							<TR>
						</TABLE>
						</DIV>
<?
	}
	include("footer.php"); 
?>


<? 
 
/* gestion_contributions.php
 * - R�capitulatif des contributions
 * Copyright (c) 2004 Fr�d�ric Jaqcuot
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
	
	$filtre_id_adh = "";
	
	if ($_SESSION["logged_status"]==0) 
		header("location: index.php");

	if ($_SESSION["admin_status"]==0)
	{
		$_SESSION["filtre_cotis_adh"] = $_SESSION["logged_id_adh"];
		$_SESSION["filtre_cotis_adh_2"] = "";
	}
	else
	{
		if (isset($_GET["id_adh"]))
		{
			if (is_numeric($_GET["id_adh"]))
				$_SESSION["filtre_cotis_adh"]=$_GET["id_adh"];
			else
				$_SESSION["filtre_cotis_adh"]="";
		}
		else
			$_SESSION["filtre_cotis_adh"]="";

		if ($_SESSION["admin_status"]<FULL_ADMIN) 
			$_SESSION["filtre_cotis_adh_2"] = $_SESSION["admin_status"];
		else
			$_SESSION["filtre_cotis_adh_2"] = "";
	}		


        if (isset($_GET["contrib_filter_1"]))
	   if (ereg("^([0-9]{2})/([0-9]{2})/([0-9]{4})$", $_GET["contrib_filter_1"], $array_jours))
	   {
	      if (checkdate($array_jours[2],$array_jours[1],$array_jours[3]))
	         $_SESSION["filtre_date_cotis_1"]=$_GET["contrib_filter_1"];
	      else
	         $error_detected .= "<LI>"._T("- Date non valide !")."</LI>";
	   }
	   elseif (ereg("^([0-9]{4})$", $_GET["contrib_filter_1"], $array_jours))
	      $_SESSION["filtre_date_cotis_1"]="01/01/".$array_jours[1];
	   elseif ($_GET["contrib_filter_1"]=="")
	      $_SESSION["filtre_date_cotis_1"]="";
	   else
	      $error_detected .= "<LI>"._T("- Mauvais format de date (jj/mm/aaaa) !")."</LI>";

	if (isset($_GET["contrib_filter_2"]))
	   if (ereg("^([0-9]{2})/([0-9]{2})/([0-9]{4})$", $_GET["contrib_filter_2"], $array_jours))
	   {
	      if (checkdate($array_jours[2],$array_jours[1],$array_jours[3]))
	         $_SESSION["filtre_date_cotis_2"]=$_GET["contrib_filter_2"];
	      else
	         $error_detected .= "<LI>"._T("- Date non valide !")."</LI>";
	   }
	   elseif (ereg("^([0-9]{4})$", $_GET["contrib_filter_2"], $array_jours))
	      $_SESSION["filtre_date_cotis_2"]="01/01/".$array_jours[1];
	   elseif ($_GET["contrib_filter_2"]=="")
	      $_SESSION["filtre_date_cotis_2"]="";
	   else
	      $error_detected .= "<LI>"._T("- Mauvais format de date (jj/mm/aaaa) !")."</LI>";

	
	$page = 1;
	if (isset($_GET["page"]))
		$page = $_GET["page"];


	// Tri
	
	if (isset($_GET["tri"]))
	{
		if ($_SESSION["tri_cotis"]==$_GET["tri"])
			$_SESSION["tri_cotis_sens"]=($_SESSION["tri_cotis_sens"]+1)%2;
		else
		{
			$_SESSION["tri_cotis"]=$_GET["tri"];
			$_SESSION["tri_cotis_sens"]=0;
		}
	}

	include("header.php");

	if ($_SESSION["admin_status"]!=0) 
	if (isset($_GET["sup"]))
	{
		// recherche adherent
		$requetesel = "SELECT id_adh
			    FROM ".PREFIX_DB."cotisations 
			    WHERE id_cotis=".$DB->qstr($_GET["sup"]); 
		$result_adh = &$DB->Execute($requetesel);
		if (!$result_adh->EOF)
		{			
			$id_adh = $result_adh->fields["id_adh"];

			$requetesup = "SELECT nom_adh, prenom_adh FROM ".PREFIX_DB."adherents WHERE id_adh=".$DB->qstr($id_adh);
			$resultat = $DB->Execute($requetesup);
			if (!$resultat->EOF)
			{			
				// supression record cotisation
				$requetesup = "DELETE FROM ".PREFIX_DB."cotisations 
				    	    WHERE id_cotis=".$DB->qstr($_GET["sup"]); 
				$DB->Execute($requetesup);
			
				// mise a jour de l'�ch�ance
				$date_fin = get_echeance($DB, $id_adh);
				if ($date_fin!="")
					$date_fin_update = $DB->DBDate(mktime(0,0,0,$date_fin[1],$date_fin[0],$date_fin[2]));
				else
					$date_fin_update = "NULL";	
				$requeteup = "UPDATE ".PREFIX_DB."adherents
					    SET date_echeance=".$date_fin_update."
					    WHERE id_adh=".$DB->qstr($id_adh);
				$DB->Execute($requeteup);
 				dblog(_T("Suppression d'une contribution :")." ".strtoupper($resultat->fields[0])." ".$resultat->fields[1], $requetesup);							
 			}
 			$resultat->Close();
 		}
 		$result_adh->Close();
	}
?> 
		<H1 class="titre"><? echo _T("Gestion des contributions"); ?></H1>
<?
	$requete[0] = "SELECT ".PREFIX_DB."cotisations.*, ".PREFIX_DB."adherents.nom_adh, ".PREFIX_DB."adherents.prenom_adh,
			".PREFIX_DB."types_cotisation.libelle_type_cotis
			FROM ".PREFIX_DB."cotisations,".PREFIX_DB."adherents,".PREFIX_DB."types_cotisation
			WHERE ".PREFIX_DB."cotisations.id_adh=".PREFIX_DB."adherents.id_adh
			AND ".PREFIX_DB."types_cotisation.id_type_cotis=".PREFIX_DB."cotisations.id_type_cotis ";
	$requete[1] = "SELECT count(id_cotis)
			FROM ".PREFIX_DB."cotisations
			WHERE 1=1 ";

	// phase filtre
	
	if ($_SESSION["filtre_cotis_adh"]!="")
	{
		$requete[0] .= "AND ".PREFIX_DB."cotisations.id_adh='" . $_SESSION["filtre_cotis_adh"] . "' ";
		$requete[1] .= "AND ".PREFIX_DB."cotisations.id_adh='" . $_SESSION["filtre_cotis_adh"] . "' ";
	}
	if ($_SESSION["filtre_cotis_adh_2"]!="")
	{
		$requete[0] .= "AND ".PREFIX_DB."adherents.id_groupe='" . $_SESSION["filtre_cotis_adh_2"] . "' ";
		$requete[1] .= "AND ".PREFIX_DB."adherents.id_groupe='" . $_SESSION["filtre_cotis_adh_2"] . "' ";
	}
	// date filter
	if ($_SESSION["filtre_date_cotis_1"]!="")
	{
	   ereg("^([0-9]{2})/([0-9]{2})/([0-9]{4})$", $_SESSION["filtre_date_cotis_1"], $array_jours);
	   $datemin = $DB->DBDate(mktime(0,0,0,$array_jours[2],$array_jours[1],$array_jours[3]));
	   $requete[0] .= "AND ".PREFIX_DB."cotisations.date_cotis >= " . $datemin . " ";
	   $requete[1] .= "AND ".PREFIX_DB."cotisations.date_cotis >= " . $datemin . " ";
	}
	if ($_SESSION["filtre_date_cotis_2"]!="")
	{
	   ereg("^([0-9]{2})/([0-9]{2})/([0-9]{4})$", $_SESSION["filtre_date_cotis_2"], $array_jours);
	   $datemax = $DB->DBDate(mktime(0,0,0,$array_jours[2],$array_jours[1],$array_jours[3]));
	   $requete[0] .= "AND ".PREFIX_DB."cotisations.date_cotis <= " . $datemax . " ";
	   $requete[1] .= "AND ".PREFIX_DB."cotisations.date_cotis <= " . $datemax . " ";
	}

	// phase de tri
	
	if ($_SESSION["tri_cotis_sens"]=="0")
		$tri_cotis_sens_txt="ASC";
	else
		$tri_cotis_sens_txt="DESC";	
								
	$requete[0] .= "ORDER BY ";

	// tri par adherent
	if ($_SESSION["tri_cotis"]=="1")
		$requete[0] .= "nom_adh ".$tri_cotis_sens_txt.", prenom_adh ".$tri_cotis_sens_txt.",";
		
	// tri par type
	elseif ($_SESSION["tri_cotis"]=="2")
		$requete[0] .= "libelle_type_cotis ".$tri_cotis_sens_txt.",";
	
	// tri par montant
	elseif ($_SESSION["tri_cotis"]=="3")
		$requete[0] .= "montant_cotis ".$tri_cotis_sens_txt.",";

	// tri par duree
	elseif ($_SESSION["tri_cotis"]=="4")
		$requete[0] .= "duree_mois_cotis ".$tri_cotis_sens_txt.",";

	// defaut : tri par date
	$requete[0] .= " ".PREFIX_DB."cotisations.date_cotis ".$tri_cotis_sens_txt; 
	
	// $resultat = &$DB->Execute($requete[0]); 
	$resultat = &$DB->SelectLimit($requete[0],PREF_NUMROWS,($page-1)*PREF_NUMROWS);
	$nbcotis = &$DB->Execute($requete[1]); 
	
	if ($nbcotis->fields[0]%PREF_NUMROWS==0) 
		$nbpages = intval($nbcotis->fields[0]/PREF_NUMROWS);
	else 
		$nbpages = intval($nbcotis->fields[0]/PREF_NUMROWS)+1;
	$pagestring = "";
	if ($nbpages==0)
		$pagestring = "<b>1</b>";
	else for ($i=1;$i<=$nbpages;$i++)
	{
		if ($i!=$page)
			$pagestring .= "<a href=\"gestion_contributions.php?page=".$i."\">".$i."</a> ";
		else
			$pagestring .= $i." ";
	}
?>
  				<DIV id="listfilter">
	                	   <FORM action="gestion_contributions.php" method="get" name="filtre">
			              <? echo _T("Afficher les contributions du"); ?>&nbsp;
				      <INPUT type="text" name="contrib_filter_1" maxlength="10" size="10" value="<? echo $_SESSION["filtre_date_cotis_1"]; ?>">
				      <? echo _T("au"); ?>&nbsp;
				      <INPUT type="text" name="contrib_filter_2" maxlength="10" size="10" value="<? echo $_SESSION["filtre_date_cotis_2"]; ?>">
				      <INPUT type="submit" value="<? echo _T("Filtrer"); ?>">
				   </FORM>
				</DIV>
						<TABLE id="infoline" width="100%">
							<TR>
								<TD class="left"><? echo $nbcotis->fields[0]." "; if ($nbcotis->fields[0]!=1) echo _T("contributions"); else echo _T("contribution"); ?></TD>
								<TD class="right"><? echo _T("Pages :"); ?> <SPAN class="pagelink"><? echo $pagestring; ?></SPAN></TD>
							</TR>
						</TABLE>
						<TABLE width="100%"> 
							<TR> 
								<TH width="15" class="listing">#</TH> 
			  					<TH class="listing left"> 
									<A href="gestion_contributions.php?tri=0&amp;id_adh=<? echo $_SESSION["filtre_cotis_adh"] ?>" class="listing"><? echo _T("Date"); ?></A>
									<?
										if ($_SESSION["tri_cotis"]=="0")
											if ($_SESSION["tri_cotis_sens"]=="0")
												echo "<IMG src=\"images/asc.png\" width=\"7\" height=\"7\" alt=\"\">";
											else 
												echo "<IMG src=\"images/desc.png\" width=\"7\" height=\"7\" alt=\"\">";
									?>
								</TH> 
<?
	if ($_SESSION["admin_status"]!=0) 
	{
?>
								<TH class="listing left"> 
									<A href="gestion_contributions.php?tri=1&amp;id_adh=<? echo $_SESSION["filtre_cotis_adh"] ?>" class="listing"><? echo _T("Adh�rent"); ?></A>
									<?
										if ($_SESSION["tri_cotis"]=="1")
											if ($_SESSION["tri_cotis_sens"]=="0")
												echo "<IMG src=\"images/asc.png\" width=\"7\" height=\"7\" alt=\"\">";
											else 
												echo "<IMG src=\"images/desc.png\" width=\"7\" height=\"7\" alt=\"\">";
									?>
								</TH> 
<?
	}
?>
								<TH class="listing left"> 
									<A href="gestion_contributions.php?tri=2&amp;id_adh=<? echo $_SESSION["filtre_cotis_adh"] ?>" class="listing"><? echo _T("Type"); ?></A>
<?
										if ($_SESSION["tri_cotis"]=="2")
											if ($_SESSION["tri_cotis_sens"]=="0")
												echo "<IMG src=\"images/asc.png\" width=\"7\" height=\"7\" alt=\"\">";
											else 
												echo "<IMG src=\"images/desc.png\" width=\"7\" height=\"7\" alt=\"\">";
									?>
								</TH> 
								<TH class="listing left"> 
									<A href="gestion_contributions.php?tri=3&amp;id_adh=<? echo $_SESSION["filtre_cotis_adh"] ?>" class="listing"><? echo _T("Montant"); ?></A>
									<?
										if ($_SESSION["tri_cotis"]=="3")
											if ($_SESSION["tri_cotis_sens"]=="0")
												echo "<IMG src=\"images/asc.png\" width=\"7\" height=\"7\" alt=\"\">";
											else 
												echo "<IMG src=\"images/desc.png\" width=\"7\" height=\"7\" alt=\"\">";
									?>
								</TH> 
								<TH class="listing left"> 
									<A href="gestion_contributions.php?tri=4&amp;id_adh=<? echo $_SESSION["filtre_cotis_adh"] ?>" class="listing"><? echo _T("Dur�e"); ?></A>
									<?
										if ($_SESSION["tri_cotis"]=="4")
											if ($_SESSION["tri_cotis_sens"]=="0")
												echo "<IMG src=\"images/asc.png\" width=\"7\" height=\"7\" alt=\"\">";
											else 
												echo "<IMG src=\"images/desc.png\" width=\"7\" height=\"7\" alt=\"\">";
									?>
								</TH> 
<?
	if ($_SESSION["admin_status"]!=0) 
	{
?>
								<TH width="55" class="listing"> 
									<? echo _T("Actions"); ?> 
								</TH> 
<?
	}
?>
							</TR> 
<? 
	$compteur = 1+($page-1)*PREF_NUMROWS;
	$activity_class = "";
	if ($resultat->EOF)
	{
		if ($_SESSION["admin_status"]!=0)
			$colspan = 7;
		else
			$colspan = 5;
?>
							<TR>
								<TD colspan="<? echo $colspan; ?>" class="emptylist"><? echo _T("aucune contribution"); ?></TD>
							</TR>
<?	
	}
	else while(!$resultat->EOF) 
	{ 
		if ($resultat->fields["duree_mois_cotis"]!="0")
			$row_class = "cotis-normal";
		else
			$row_class = "cotis-give";
?>							 
							<TR> 
								<TD width="15" class="<? echo $row_class; ?> center" nowrap><? echo $compteur ?></TD> 
								<TD width="50" class="<? echo $row_class; ?>" nowrap> 
									<?
										list($a,$m,$j)=split("-",$resultat->fields["date_cotis"]);
										echo "$j/$m/$a"; 
									?> 
								</TD> 
<?
	if ($_SESSION["admin_status"]!=0) 
	{
?>
								<TD class="<? echo $row_class; ?>" nowrap> 
									<A href="gestion_contributions.php?id_adh=<? echo $resultat->fields["id_adh"] ?>"><?
										echo htmlentities(strtoupper($resultat->fields["nom_adh"]), ENT_QUOTES)." ";
										if (isset($resultat->fields["prenom_adh"]))
											echo htmlentities($resultat->fields["prenom_adh"], ENT_QUOTES);
									?></A> 
								</TD> 
<?
	}
?>
								<TD class="<? echo $row_class; ?>" nowrap><? echo _T($resultat->fields["libelle_type_cotis"]) ?></TD> 
								<TD class="<? echo $row_class; ?>" nowrap><? echo $resultat->fields["montant_cotis"] ?></TD> 
								<TD class="<? echo $row_class; ?>" nowrap><? echo $resultat->fields["duree_mois_cotis"] ?></TD> 
<?
	if ($_SESSION["admin_status"]!=0) 
	{
?>
								<TD width="55" class="<? echo $row_class; ?> center" nowrap>  
									<A href="ajouter_contribution.php?id_cotis=<? echo $resultat->fields["id_cotis"] ?>"><IMG src="images/icon-edit.png" alt="<? echo _T("[mod]"); ?>" border="0" width="12" height="13"></A>
									<A onClick="return confirm('<? echo str_replace("\n","\\n",addslashes(_T("Voulez-vous vraiment supprimer cette contribution de la base ?"))); ?>')" href="gestion_contributions.php?sup=<? echo $resultat->fields["id_cotis"] ?>"><IMG src="images/icon-trash.png" alt="<? echo _T("[sup]"); ?>" border="0" width="11" height="13"></A>
								</TD> 
<?
	}

		$compteur++;
		$resultat->MoveNext();
	}
	$resultat->Close();
?>
						</TABLE>
						<DIV id="infoline2" class="right"><? echo _T("Pages :"); ?> <SPAN class="pagelink"><? echo $pagestring; ?></SPAN></DIV>
<?	
	// affichage du temps d'ah�sion restant si on est en train de visualiser
	// les cotisations d'un membre unique
	
	if ($_SESSION["filtre_cotis_adh"]!="")
	{
		$requete = "SELECT date_echeance, bool_exempt_adh
			    FROM ".PREFIX_DB."adherents
			    WHERE id_adh='".$_SESSION["filtre_cotis_adh"]."'";
		$resultat = $DB->Execute($requete);
		
		// temps d'adh�sion
		if($resultat->fields[1])
		{
			$statut_cotis = _T("Exempt de cotisation");
			$color = "#DDFFDD";
		}
		else
		{
			if ($resultat->fields[0]=="")
			{
				$statut_cotis = _T("N'a jamais cotis�");
				$color = "#EEEEEE";			
			}
			else
			{
			
			
			$date_fin = split("-",$resultat->fields[0]);
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
				$statut_cotis = _T("En retard de")." ".-$difference." "._T("jours")." ("._T("depuis le")." ".$date_fin[2]."/".$date_fin[1]."/".$date_fin[0].")";
				$color = "#FFDDDD";
			}
			else
			{
				if ($difference!=1)
					$statut_cotis = $difference." "._T("jours restants")." ("._T("fin le")." ".$date_fin[2]."/".$date_fin[1]."/".$date_fin[0].")";
				else
					$statut_cotis = $difference." "._T("jour restant")." ("._T("fin le")." ".$date_fin[2]."/".$date_fin[1]."/".$date_fin[0].")";
				if ($difference < 30)
					$color = "#FFE9AB";
				else
					$color = "#DDFFDD";	
			}
			
			}
		}		
		
		
		/*$days_left = get_days_left($DB, $_SESSION["filtre_cotis_adh"]);
		$cumul = $days_left["cumul"];
		$statut_cotis = $days_left["text"];
		$color = $days_left["color"];*/
?>	
		<BR>
		<DIV align="center">
		  <TABLE bgcolor="<? echo $color; ?>">
		    <TR>
		      <TD><? echo $statut_cotis; ?></TD>
		    </TR>
		  </TABLE>
<?
		if ($_SESSION["admin_status"]!=0)
	        {
?>
	<BR>
	<A href="voir_adherent.php?id_adh=<? echo $_SESSION["filtre_cotis_adh"]; ?>"><? echo _T("[ Voir la fiche adh�rent ]"); ?></A>
	&nbsp;&nbsp;&nbsp;
	<A href="ajouter_contribution.php?id_adh=<? echo $_SESSION["filtre_cotis_adh"]; ?>"><? echo _T("[ Ajouter une contribution ]"); ?></A>
<?
		}	
?>
		</DIV>
<?
	}	
?>							 

<? 
  include("footer.php"); 
?>

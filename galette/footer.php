<?
/* footer.php
 * - Pied de page
 * Copyright (c) 2003 Fr�d�ric Jaqcuot
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

	$end = utime(); $run = $end - $start;
?>
		<DIV id="copyright">
			<A href="http://galette.tuxfamily.org/fr/doku.php">Galette <? echo GALETTE_VERSION ?></A> - <? echo _T("R�alisation :"); ?> <A href="mailto:deelight@logeek.com">Deelight</A> - Hack Sport : <A href="mailto:flattwin12@free.fr">Flattwin</A> - <? echo _T("Graphisme :"); ?> <A href="http://www.gimp.org">Gimp</A> - <? echo _T("Editeur :"); ?> <A href="http://bluefish.openoffice.nl">Bluefish</A> - <? echo _T("Page affich�e en")." ".substr($run, 0, 5)." "._T("secondes."); ?>
		</DIV>
	</DIV>
	<DIV id="menu">
		<DIV id="logo">
			<IMG src="images/galette.jpg" alt="[ Galette ]" width="103" height="80"><BR>
			Galette
		</DIV>	
		<DIV id="nav1">
			<H1><? echo _T("Navigation"); ?></H1>
			<UL>
				<LI><? echo $_SESSION["logged_nom_adh"]; ?></LI>
<?
	if ($_SESSION["admin_status"]!=0) 
	{
?>
				<LI><A href="gestion_adherents.php"><? echo _T("Liste des adh�rents"); ?></A></LI>
				<LI><A href="gestion_contributions.php"><? echo _T("Liste des contributions"); ?></A></LI>
				<LI><A href="ajouter_adherent.php"><? echo _T("Ajouter un adh�rent"); ?></A></LI>
				<LI><A href="ajouter_contribution.php"><? echo _T("Ajouter une contribution"); ?></A></LI>
				<LI><A href="mailing_adherents.php"><? echo _T("Effectuer un mailing"); ?></A></LI>
				<LI><A href="mailing_adherents.php?etiquettes=1"><? echo _T("G�n�ration d'�tiquettes"); ?></A></LI>
<?
	}
	//Only real super admin is able to access logs and preferences, not users which have been granted a all users admin right
	//if ($_SESSION["admin_status"]==SUPER_ADMIN) 
	//if ($_SESSION["logged_username"]==PREF_ADMIN_LOGIN)  // Doesn't work !?
	//if ($_SESSION["logged_nom_adh"]==_T("Administrateur"))
	if ($_SESSION["admin_status"]==SITE_ADMIN)
	{
?>
				<LI><A href="log.php"><? echo _T("Historique"); ?></A></LI>
				<LI><A href="preferences.php"><? echo _T("Pr�f�rences"); ?></A></LI>
				<LI><A href="import.php"><? echo _T("Import CSV"); ?></A></LI>
				<LI><A href="export.php"><? echo _T("Export CSV"); ?></A></LI>
<?
	}
	else
	{
?>
				<LI><A href="voir_adherent.php"><? echo _T("Mes informations"); ?></A></LI>
				<LI><A href="gestion_contributions.php"><? echo _T("Mes contributions"); ?></A></LI>
<?
	}
?>				
			</UL>
		</DIV>
		<DIV id="logout">
			<A href="index.php?logout=1"><? echo _T("D�connexion"); ?></A>
		</DIV>
<? 
	if (basename($_SERVER["SCRIPT_NAME"])=="gestion_adherents.php" || basename($_SERVER["SCRIPT_NAME"])=="mailing_adherents.php") 
	{
?>
		<DIV id="legende">
			<H1><? echo _T("L�gende"); ?></H1>
			<TABLE>
				<TR>
					<TD width="30" class="back"><IMG src="images/icon-male.png" Alt="<? echo _T("[H]"); ?>" align="middle" width="10" height="12"></TD>
					<TD class="back"><? echo _T("Homme"); ?></TD>
				</TR>
				<TR>
					<TD width="30" class="back"><IMG src="images/icon-female.png" Alt="<? echo _T("[F]"); ?>" align="middle" width="9" height="12"></TD>
					<TD class="back"><? echo _T("Femme"); ?></TD>
				</TR>
<?
		if (basename($_SERVER["SCRIPT_NAME"])=="gestion_adherents.php")
		{
?>
				<TR>
					<TD width="30" class="back"><IMG src="images/icon-mail.png" Alt="<? echo _T("[Mail]"); ?>" align="middle" border="0" width="14" height="10"></TD>
					<TD class="back"><? echo _T("Envoyer un mail"); ?></TD>
				</TR>
<?
		}
?>
				<TR>
					<TD width="30" class="back"><IMG src="images/icon-star.png" Alt="<? echo _T("[admin]"); ?>" align="middle" width="12" height="13"></TD>
					<TD class="back"><? echo _T("Administrateur"); ?></TD>
				</TR>
				<TR>
					<TD width="30" class="back"><IMG src="images/icon-edit.png" alt="<? echo _T("[mod]"); ?>" border="0" width="12" height="13"></TD>
					<TD class="back"><? echo _T("Modification"); ?></TD>
				</TR>
				<TR>
					<TD width="30" class="back"><IMG src="images/icon-money.png" alt="<? echo _T("[$]"); ?>" border="0" width="13" height="13"></TD>
					<TD class="back"><? echo _T("Contributions"); ?></TD>
				</TR>
				<TR>
					<TD width="30" class="back"><IMG src="images/icon-trash.png" alt="<? echo _T("[sup]"); ?>" border="0" width="11" height="13"></TD>
					<TD class="back"><? echo _T("Suppression"); ?></TD>
				</TR>
				<TR>
					<TD width="30" class="back"><? echo _T("Nom"); ?></TD>
					<TD class="back"><? echo _T("Compte actif"); ?></TD>
				</TR>
				<TR>
					<TD width="30" class="inactif back"><? echo _T("Nom"); ?></TD>
					<TD class="back"><? echo _T("Compte d�sactiv�"); ?></TD>
				</TR>
				<TR>
					<TD width="30" class="cotis-never color-sample">&nbsp;</TD>
					<TD class="back"><? echo _T("N'a jamais cotis�"); ?></TD>
				</TR>
				<TR>
					<TD width="30" class="cotis-ok color-sample">&nbsp;</TD>
					<TD class="back"><? echo _T("Adh�sion en r�gle"); ?></TD>
				</TR>
				<TR>
					<TD width="30" class="cotis-soon color-sample">&nbsp;</TD>
					<TD class="back"><? echo _T("Adh�sion � �ch�ance (<30j)"); ?></TD>
				</TR>
				<TR>
					<TD width="30" class="cotis-late color-sample">&nbsp;</TD>
					<TD class="back"><? echo _T("Retard de cotisation"); ?></TD>
				</TR>
			</TABLE>
		</DIV>
<?
	}
	elseif (basename($_SERVER["SCRIPT_NAME"])=="gestion_contributions.php") 
	{
?>
		<DIV id="legende">
			<H1><? echo _T("L�gende"); ?></H1>
			<TABLE>
<?
		if ($_SESSION["admin_status"]==1) 
		{
?>
				<TR>
					<TD width="30" class="back"><IMG src="images/icon-edit.png" alt="<? echo _T("[mod]"); ?>" border="0" width="12" height="13"></TD>
					<TD class="back"><? echo _T("Modification"); ?></TD>
				</TR>
				<TR>
					<TD width="30" class="back"><IMG src="images/icon-trash.png" alt="<? echo _T("[sup]"); ?>" border="0" width="11" height="13"></TD>
					<TD class="back"><? echo _T("Suppression"); ?></TD>
				</TR>
<?
		}
?>
				<TR>
					<TD width="30" class="cotis-normal color-sample">&nbsp;</TD>
					<TD class="back"><? echo _T("Cotisation"); ?></TD>
				</TR>
				<TR>
					<TD width="30" class="cotis-give color-sample">&nbsp;</TD>
					<TD class="back"><? echo _T("Don"); ?></TD>
				</TR>
			</TABLE>
		</DIV>
<?
	}
?>
	</DIV>
</BODY> 
</HTML>

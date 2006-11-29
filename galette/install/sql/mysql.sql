DROP TABLE galette_adherents;
CREATE TABLE galette_adherents (
  id_adh int(10) unsigned NOT NULL auto_increment,
  id_statut int(10) unsigned NOT NULL default '4',
  nom_adh varchar(20) NOT NULL default '',
  prenom_adh varchar(20) default NULL,
  pseudo_adh varchar(20) default NULL,
  titre_adh tinyint(3) unsigned NOT NULL default '0',
  ddn_adh date default NULL,
  ldn_adh varchar(50) default NULL,
  adresse_adh varchar(150) NOT NULL default '',
  adresse2_adh varchar(150) default NULL,
  cp_adh varchar(10) NOT NULL default '',
  ville_adh varchar(50) NOT NULL default '',
  pays_adh varchar(50) default NULL,
  tel_adh varchar(20) default NULL,
  tel_pro_adh varchar(20) default NULL,
  gsm_adh varchar(20) default NULL,
  email_adh varchar(150) default NULL,
  email_pro_adh varchar(150) default NULL,
  url_adh varchar(200) default NULL,
  icq_adh varchar(20) default NULL,
  msn_adh varchar(150) default NULL,
  jabber_adh varchar(150) default NULL,
  info_adh text,
  info_public_adh text,
  prof_adh varchar(150) default NULL,
  login_adh varchar(20) NOT NULL default '',
  mdp_adh varchar(20) NOT NULL default '',
  date_crea_adh date NOT NULL default '0000-00-00',
  activite_adh enum('0','1') NOT NULL default '0',
  bool_admin_adh int(11) NOT NULL default '0',
  bool_exempt_adh enum('1') default NULL,
  bool_display_info enum('1') default NULL,
  date_echeance date default NULL,
  id_groupe int(11) default '1',
  id_lic int(10) default '1',
  lic_adh varchar(20) default NULL,
  date_licence date default NULL,
  id_categorie int(11) default NULL,
  medecin_certif varchar(32) default NULL,
  date_certif date default NULL,
  dif_tel enum('OUI','NON') default 'NON',
  dif_email enum('OUI','NON') default 'NON',
  dif_image enum('OUI','NON') default 'NON',
  enveloppe enum('OUI','NON FOURNIE') default 'NON FOURNIE',
  photo enum('OUI','NON FOURNIE') default 'NON FOURNIE',
  attestation enum('OUI','NON') default 'NON',
  participation_vet enum('OUI','NON') default 'NON',
  referent int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (id_adh)
) TYPE=MyISAM;

DROP TABLE galette_cotisations;
CREATE TABLE galette_cotisations (
  id_cotis int(10) unsigned NOT NULL auto_increment,
  id_adh int(10) unsigned NOT NULL default '0',
  id_type_cotis int(10) unsigned NOT NULL default '0',
  montant_cotis float unsigned default '0',
  info_cotis text,
  duree_mois_cotis tinyint(3) unsigned NOT NULL default '12',
  date_cotis date NOT NULL default '0000-00-00',
  PRIMARY KEY  (id_cotis)
) TYPE=MyISAM;

DROP TABLE galette_statuts;
CREATE TABLE galette_statuts (
  id_statut int(10) unsigned NOT NULL auto_increment,
  libelle_statut varchar(20) NOT NULL default '',
  priorite_statut tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (id_statut)
) TYPE=MyISAM;


INSERT INTO galette_statuts VALUES (1,'Président',0);
INSERT INTO galette_statuts VALUES (10,'Vice-président',5);
INSERT INTO galette_statuts VALUES (2,'Trésorier',10);
INSERT INTO galette_statuts VALUES (4,'Membre actif',30);
INSERT INTO galette_statuts VALUES (5,'Membre bienfaiteur',40);
INSERT INTO galette_statuts VALUES (6,'Membre fondateur',50);
INSERT INTO galette_statuts VALUES (3,'Secrétaire',20);
INSERT INTO galette_statuts VALUES (7,'Ancien',60);
INSERT INTO galette_statuts VALUES (8,'Personne morale',70);
INSERT INTO galette_statuts VALUES (9,'Non membre',80);

DROP TABLE galette_types_cotisation;
CREATE TABLE galette_types_cotisation (
  id_type_cotis int(10) unsigned NOT NULL auto_increment,
  libelle_type_cotis varchar(30) NOT NULL default '',
  PRIMARY KEY  (id_type_cotis)
) TYPE=MyISAM;


INSERT INTO galette_types_cotisation VALUES (1,'Cotisation annuelle normale');
INSERT INTO galette_types_cotisation VALUES (2,'Cotisation annuelle réduite');
INSERT INTO galette_types_cotisation VALUES (3,'Cotisation entreprise');
INSERT INTO galette_types_cotisation VALUES (4,'Donation en nature');
INSERT INTO galette_types_cotisation VALUES (5,'Donation pécunière');
INSERT INTO galette_types_cotisation VALUES (6,'Partenariat');

DROP TABLE galette_preferences;
CREATE TABLE galette_preferences (
  id_pref int(10) unsigned NOT NULL auto_increment,
  nom_pref varchar(100) NOT NULL default '',
  val_pref varchar(200) NOT NULL default '',
  PRIMARY KEY  (id_pref)
) TYPE=MyISAM;

DROP TABLE galette_logs;
CREATE TABLE galette_logs (
  id_log int(10) unsigned NOT NULL auto_increment,
  date_log datetime NOT NULL default '0000-00-00 00:00:00',
  ip_log varchar(30) NOT NULL default '',
  adh_log varchar(41) NOT NULL default '',
  text_log text,
  PRIMARY KEY  (id_log)
) TYPE=MyISAM;

DROP TABLE galette_types_licences;
CREATE TABLE galette_types_licences (
  id_type_lic int(10) unsigned NOT NULL auto_increment,
  libelle_type_lic varchar(32) NOT NULL default '',
  PRIMARY KEY  (id_type_lic)
) TYPE=MyISAM ROW_FORMAT=DYNAMIC;

INSERT INTO galette_types_licences VALUES (1, 'FFRS Loisir Randonnée');
INSERT INTO galette_types_licences VALUES (2, 'FFRS Compétion Course');
INSERT INTO galette_types_licences VALUES (3, 'UFOLEP');

DROP TABLE galette_categories;
CREATE TABLE galette_categories (
  id_categorie int(10) unsigned NOT NULL auto_increment,
  libelle_categorie varchar(32) NOT NULL default '',
  abbrev_categorie varchar(4) default NULL,
  PRIMARY KEY  (id_categorie)
) TYPE=MyISAM;

INSERT INTO galette_categories VALUES (1, 'Super Mini (6-7 ans)', 'SM');
INSERT INTO galette_categories VALUES (2, 'Mini (8-9 ans)', 'MI');
INSERT INTO galette_categories VALUES (3, 'Poussin (10-11 ans)', 'PO');
INSERT INTO galette_categories VALUES (4, 'Espoir', 'ES');
INSERT INTO galette_categories VALUES (5, 'Benjamin (12-13 ans)', 'BE');
INSERT INTO galette_categories VALUES (6, 'Cadet (16-17 ans)', 'CA');
INSERT INTO galette_categories VALUES (7, 'Minime (14-15 ans)', 'MN');
INSERT INTO galette_categories VALUES (8, 'Jeunesse', 'JE');
INSERT INTO galette_categories VALUES (9, 'Junior (18-19 ans)', 'JU');
INSERT INTO galette_categories VALUES (10, 'Senior (20 et plus)', 'SE');
INSERT INTO galette_categories VALUES (11, 'Vétéran 1 (35-49 ans)', 'V1');
INSERT INTO galette_categories VALUES (12, 'Vétéran 2 (50 et plus)', 'V2');

DROP TABLE galette_groupes;
CREATE TABLE galette_groupes (
  id_groupe int(10) unsigned NOT NULL auto_increment,
  libelle_groupe varchar(20) NOT NULL default 'nom du groupe',
  PRIMARY KEY  (id_groupe)
) TYPE=MyISAM;

INSERT INTO galette_groupes VALUES (1, 'Débutants');
INSERT INTO galette_groupes VALUES (2, 'Perfectionnement');
INSERT INTO galette_groupes VALUES (3, 'Endurance');
INSERT INTO galette_groupes VALUES (4, 'Initiation Vitesse');
INSERT INTO galette_groupes VALUES (5, 'Compétition');

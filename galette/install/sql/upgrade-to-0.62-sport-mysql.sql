ALTER TABLE galette_adherents ADD ldn_adh varchar(50) default NULL AFTER ddn_adh;
ALTER TABLE galette_adherents ADD tel_pro_adh varchar(20) default NULL AFTER tel_adh;
ALTER TABLE galette_adherents ADD email_pro_adh varchar(150) default NULL AFTER email_adh;
ALTER TABLE galette_adherents MODIFY bool_admin_adh int(11) NOT NULL default '0';
ALTER TABLE galette_adherents ADD id_groupe int(11) default '1' AFTER date_echeance;
ALTER TABLE galette_adherents ADD id_lic int(10) default '1' AFTER id_groupe;
ALTER TABLE galette_adherents ADD lic_adh varchar(20) default NULL AFTER id_lic;
ALTER TABLE galette_adherents ADD date_licence date default NULL AFTER lic_adh;
ALTER TABLE galette_adherents ADD id_categorie int(11) default NULL AFTER date_licence;
ALTER TABLE galette_adherents ADD medecin_certif varchar(32) default NULL AFTER id_categorie;
ALTER TABLE galette_adherents ADD date_certif date default NULL AFTER medecin_certif;
ALTER TABLE galette_adherents ADD dif_tel enum('OUI','NON') default 'NON' AFTER date_certif;
ALTER TABLE galette_adherents ADD dif_email enum('OUI','NON') default 'NON' AFTER dif_tel;
ALTER TABLE galette_adherents ADD dif_image enum('OUI','NON') default 'NON' AFTER dif_email;
ALTER TABLE galette_adherents ADD enveloppe enum('OUI','NON FOURNIE') default 'NON FOURNIE' AFTER dif_image;
ALTER TABLE galette_adherents ADD photo enum('OUI','NON FOURNIE') default 'NON FOURNIE' AFTER enveloppe;
ALTER TABLE galette_adherents ADD attestation enum('OUI','NON') default 'NON' AFTER photo;
ALTER TABLE galette_adherents ADD participation_vet enum('OUI','NON') default 'NON' AFTER attestation;
ALTER TABLE galette_adherents ADD referent int(10) unsigned NOT NULL default '0' AFTER participation_vet;

ALTER TABLE galette_logs MODIFY date_log datetime NOT NULL default '0000-00-00 00:00:00';

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

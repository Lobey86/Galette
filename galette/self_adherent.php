<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Member creation from member itself
 *
 * PHP version 5
 *
 * Copyright © 2004-2012 The Galette Team
 *
 * This file is part of Galette (http://galette.tuxfamily.org).
 *
 * Galette is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Galette is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Galette. If not, see <http://www.gnu.org/licenses/>.
 *
 * @category  Main
 * @package   Galette
 *
 * @author    Frédéric Jaqcuot <unknown@unknow.com>
 * @author    Georges Khaznadar (password encryption, images) <unknown@unknow.com>
 * @author    Johan Cwiklinski <johan@x-tnd.be>
 * @copyright 2004-2012 The Galette Team
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GPL License 3.0 or (at your option) any later version
 * @version   SVN: $Id$
 * @link      http://galette.tuxfamily.org
 * @since     Available since 0.62
 */

/** @ignore */
require_once 'includes/galette.inc.php';
if ( !$preferences->pref_bool_selfsubscribe ) {
    header('location:index.php');
}

require_once WEB_ROOT . 'includes/dynamic_fields.inc.php';

// flagging required fields
require_once WEB_ROOT . 'classes/required.class.php';
$requires = new Required();
$required = $requires->getRequired();

require_once WEB_ROOT . 'classes/texts.class.php';
require_once 'classes/adherent.class.php';

$member = new Adherent();
//mark as self membership
$member->setSelfMembership();

// disable some fields
$disabled  = $member->disabled_fields;

// DEBUT parametrage des champs
// On recupere de la base la longueur et les flags des champs
// et on initialise des valeurs par defaut

$update_string = '';
$insert_string_fields = '';
$insert_string_values = '';
$has_register = false;


$fields = Adherent::getDbFields();

// checking posted values for 'regular' fields
if ( isset($_POST["nom_adh"]) ) {
    $adherent['dyn'] = extract_posted_dynamic_fields($_POST, $disabled);
    $valid = $member->check($_POST, $required, $disabled);
    if ( $valid === true ) {
        //all goes well, we can proceed
        $store = $member->store();
        if ( $store === true ) {
            //member has been stored :)
            //Send email to admin if preference checked
            if ( $preferences->pref_mail_method > GaletteMail::METHOD_DISABLED
                && $preferences->pref_bool_mailadh
            ) {
                $texts = new Texts(
                    array(
                        'name_adh'      => custom_html_entity_decode($member->sname),
                        'mail_adh'      => custom_html_entity_decode($member->email),
                        'login_adh'     => custom_html_entity_decode($member->login)
                    )
                );
                $mtxt = $texts->getTexts('newselfadh', $preferences->pref_lang);

                $mail = new GaletteMail();
                $mail->setSubject($texts->getSubject());
                $mail->setRecipients(
                    array(
                        $preferences->pref_email_newadh => 'Galette admin'
                    )
                );
                $mail->setMessage($texts->getBody());
                $sent = $mail->send();

                if ( $sent == GaletteMail::MAIL_SENT ) {
                    $hist->add(
                        str_replace(
                            '%s',
                            $member->sname . ' (' . $member->email . ')',
                            _T("New account mail sent to admin for '%s'.")
                        )
                    );
                } else {
                    $str = str_replace(
                        '%s',
                        $member->sname . ' (' . $member->email . ')',
                        _T("A problem happened while sending email to admin for account '%s'.")
                    );
                    $hist->add($str);
                    $error_detected[] = $str;
                }

                unset($texts);
            }

            // send mail to member
            if ( $preferences->pref_mail_method > GaletteMail::METHOD_DISABLED
                && $member->email != ''
            ) {
                //send mail to member
                // Get email text in database
                $texts = new Texts(
                    array(
                        'name_adh'      => custom_html_entity_decode($member->sname),
                        'mail_adh'      => custom_html_entity_decode($member->email),
                        'login_adh'     => custom_html_entity_decode($member->login),
                        'password_adh'  => custom_html_entity_decode($_POST['mdp_adh'])
                    )
                );
                $mtxt = $texts->getTexts('sub', $preferences->pref_lang);

                $mail = new GaletteMail();
                $mail->setSubject($texts->getSubject());
                $mail->setRecipients(
                    array(
                        $member->email => $member->sname
                    )
                );
                $mail->setMessage($texts->getBody());
                $sent = $mail->send();

                if ( $sent == GaletteMail::MAIL_SENT ) {
                    $hist->add(
                        str_replace(
                            '%s',
                            $member->sname . ' (' . $member->email . ')',
                            _T("New account mail sent to '%s'.")
                        )
                    );
                } else {
                    $str = str_replace(
                        '%s',
                        $member->sname . ' (' . $member->email . ')',
                        _T("A problem happened while sending new account mail to '%s'")
                    );
                    $hist->add($str);
                    $error_detected[] = $str;
                }
            }

            $hist->add(
                _T("Self_subscription as a member: ") .
                strtoupper($adherent['nom_adh']) . ' ' . $adherent['prenom_adh'],
                $requete
            );
            $head_redirect = '<meta http-equiv="refresh" content="10;url=index.php" />';
            $has_register = true;
        } else {
            //something went wrong :'(
            $error_detected[] = _T("An error occured while storing the member.");
        }
    } else {
        //hum... there are errors :'(
        $error_detected = $valid;
    }
} elseif ( isset($_POST["update_lang"]) && $_POST["update_lang"] == 1 ) {
    while ( list($key, $properties) = each($fields) ) {
        $key = strtolower($key);
        if ( isset($_POST[$key]) ) {
            $adherent[$key] = trim($_POST[$key]);
        } else {
            $adherent[$key] = '';
        }
    }
}

// - declare dynamic fields for display
$disabled['dyn'] = array();
if ( !isset($adherent['dyn']) ) {
    $adherent['dyn'] = array();
}

//image to defeat mass filling forms
$spam_pass = PasswordImage();
$s = PasswordImageName($spam_pass);
$spam_img = print_img($s);

$dynamic_fields = prepare_dynamic_fields_for_display(
    'adh', $adherent['dyn'], $disabled['dyn'], 1
);

// template variable declaration
$tpl->assign('page_title', _T("Subscription"));
// template variable declaration
$tpl->assign('required', $required);
$tpl->assign('disabled', $disabled);
$tpl->assign('member', $member);
$tpl->assign('self_adh', true);
$tpl->assign('dynamic_fields', $dynamic_fields);
$tpl->assign('error_detected', $error_detected);
$tpl->assign('warning_detected', $warning_detected);
$tpl->assign('languages', $i18n->getList());
$tpl->assign('require_calendar', true);
// pseudo random int
$tpl->assign('time', time());
// genre
$tpl->assign('radio_titres', Politeness::getList());

//self_adh specific
$tpl->assign('spam_pass', $spam_pass);
$tpl->assign('spam_img', $spam_img);

if ( $has_register ) {
    $tpl->assign('has_register', $has_register);
}
if ( isset($head_redirect) ) {
    $tpl->assign('head_redirect', $head_redirect);
}
// /self_adh specific

// Load needed Zend classes
require_once 'Zend/Loader.php';
Zend_Loader::loadClass('Zend_View');
Zend_Loader::loadClass('Zend_Form');
Zend_Loader::loadClass('Zend_Form_SubForm');
Zend_Loader::loadClass('Zend_Form_Element_Text');
Zend_Loader::loadClass('Zend_Form_Element_Hidden');
Zend_Loader::loadClass('Zend_Form_Element_Checkbox');
Zend_Loader::loadClass('Zend_Form_Decorator_HtmlTag');
Zend_Loader::loadClass('Zend_Form_Decorator_Label');
Zend_Loader::loadClass('Zend_Validate_EmailAddress');
Zend_Loader::loadClass('Zend_Form_Decorator_FormElements');

require_once WEB_ROOT . 'classes/forms/text_element.class.php';
require_once WEB_ROOT . 'classes/forms/hidden_element.class.php';
require_once WEB_ROOT . 'classes/forms/form.class.php';

$gform = new GaletteForm(Adherent::TABLE);

$gform->setView(new Zend_View());

$tpl->assign('zform', $gform);

// display page
$content = $tpl->fetch('member.tpl');
$tpl->assign('content', $content);
$tpl->display('public_page.tpl');
?>

<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Add a new contribution
 *
 * PHP version 5
 *
 * Copyright © 2004-2011 The Galette Team
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
 * @author    Frédéric Jacquot <unknown@unknwown.com>
 * @author    Johan Cwiklinski <johan@x-tnd.be>
 * @copyright 2004-2011 The Galette Team
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GPL License 3.0 or (at your option) any later version
 * @version   SVN: $Id$
 * @link      http://galette.tuxfamily.org
 * @since     Available since 0.62
 */

require_once 'includes/galette.inc.php';

if ( !$login->isLogged() ) {
    header('location: index.php');
    die();
}
if ( !$login->isAdmin() ) {
    header('location: voir_adherent.php');
    die();
}

require_once 'classes/texts.class.php';
require_once 'classes/contributions_types.class.php';
require_once 'includes/dynamic_fields.inc.php';
require_once 'classes/varslist.class.php';

$contrib = new Contribution();

// initialize warning
$error_detected = array();

$id_cotis = get_numeric_form_value('id_cotis', '');

//first/second step: select member
$id_adh = get_numeric_form_value('id_adh', '');
//first/second step: select contribution type
$selected_type = get_form_value('id_type_cotis', '');
//first/second step: transaction id
$trans_id = get_numeric_form_value('trans_id', '');
//mark first step has been passed
$type_selected = $id_cotis != null || get_form_value('type_selected', 0);

// flagging required fields for first step only
$required = array(
    'id_type_cotis'     => 1,
    'id_adh'            => 1
);

$cotis_extension = 0; // TODO: remove and remplace with $contrib->isCotis()
$disabled = array();

if ( $type_selected && !($id_adh || $id_cotis) ) {
    $error_detected[] = _T("You have to select a member.");
    $type_selected = false;
} else if ( $id_cotis != '' || $type_selected || $trans_id || $id_adh) {
    if ( $id_cotis != '' ) {
        $contrib = new Contribution((int)$id_cotis);
        if ( $contrib->id == '' ) {
            //not possible to load contribution, exit
            header('location: index.php');
        }
    } else {
        $args = array(
                'type'  => $selected_type,
                'adh'   => $id_adh
        );
        if ( $trans_id != '' ) {
            $args['trans'] = $trans_id;
        }
        if ( $preferences->pref_membership_ext != '' ) {
            $args['ext'] = $preferences->pref_membership_ext;
        }
        $contrib = new Contribution($args);
        if ( $contrib->isTransactionPart() ) {
            $id_adh = $contrib->member;
            //Should we disable contribution member selection if we're from
            //a transaction? In most cases, it would be OK I guess, but I'm
            //very unsure
            //$disabled['id_adh'] = ' disabled="disabled"';
        }
    }

    //second step only: first step, and all the rest
    // flagging required fields for second step
    $second_required = array(
        'montant_cotis'     => 1,
        'date_debut_cotis'  => 1,
        'date_fin_cotis'    => $contrib->isCotis(),
    );
    $required = $required + $second_required;

}

// Validation
$contribution['dyn'] = array();
if ( isset($_POST['valid']) ) {
    $contribution['dyn'] = extract_posted_dynamic_fields($_POST, array());

    $valid = $contrib->check($_POST, $required, $disabled);
    if ( $valid === true ) {
        //all goes well, we can proceed
        if ( $contrib->isCotis() ) {
            // Check that membership fees does not overlap
            $overlap = $contrib->checkOverlap();
            if ( $overlap !== true ) {
                if ( $overlap === false ) {
                    $error_detected[] = _T("An eror occured checking overlaping fees :(");
                } else {
                    //method directly return erro message
                    $error_detected[] = $overlap;
                }
            } else {

            }
        }
        $new = false;
        if ( $contrib->id == '' ) {
            $new = true;
        }

        $store = $contrib->store();
        if ( $store === true ) {
            //contribution has been stored :)
            if ( $new ) {
                /** FIXME: do something ! */
            }
        } else {
            //something went wrong :'(
            $error_detected[] = _T("An error occured while storing the contribution.");
        }
    } else {
        //hum... there are errors :'(
        $error_detected = $valid;
    }

    if ( count($error_detected) == 0 ) {
        // dynamic fields
        set_all_dynamic_fields(
            'contrib',
            $contribution['id_cotis'],
            $contribution['dyn']
        );

        // Get member informations
        $adh = new Adherent();
        $adh->load($contrib->member);
        $texts = new texts();

        if ( isset($_POST['mail_confirm']) && $_POST['mail_confirm'] == '1' ) {
            if ( GaletteMail::isValidEmail($adh->email) ) {
                $mtxt = $texts->getTexts('contrib', $adh->language);

                // Replace Tokens
                $regs = array(
                  '/{NAME}/',
                  '/{DEADLINE}/',
                  '/{COMMENT}/'
                );

                $replacements = array(
                    $preferences->pref_nom,
                    custom_html_entity_decode($contrib->end_date),
                    custom_html_entity_decode($contrib->info)
                );

                $body = preg_replace(
                    $regs,
                    $replacements,
                    $mtxt->tbody
                );

                $mail = new GaletteMail();
                $mail->setSubject($mtxt->tsubject);
                $mail->setRecipients(
                    array(
                        $adh->email => $adh->sname
                    )
                );

                $mail->setMessage($body);
                $sent = $mail->send();

                if ( $sent ) {
                    $hist->add(
                        preg_replace(
                            array('/%name/', '/%email/'),
                            array($adh->sname, $adh->email),
                            _T("Mail sent to user %name (%email)")
                        )
                    );
                } else {
                    $txt = preg_replace(
                        array('/%name/', '/%email/'),
                        array($adh->sname, $adh->email),
                        _T("A problem happened while sending contribution receipt to user %name (%email)")
                    );
                    $hist->add($txt);
                    $error_detected[] = $txt;
                }
            } else {
                $txt = preg_replace(
                    array('/%name/', '/%email/'),
                    array($adh->sname, $adh->email),
                    _T("Trying to send a mail to a member (%name) with an invalid adress: %email")
                );
                $hist->add($txt);
                $warning_detected[] = $txt;
            }
        }

        // Sent email to admin if pref checked
        if ( $preferences->pref_bool_mailadh ) {
            // Get email text in database
            $mtxt = $texts->getTexts('newcont', $preferences->pref_lang);

            $mtxt->tsubject = str_replace(
                '{NAME_ADH}',
                $adh->sname,
                $mtxt->tsubject
            );

            // Replace Tokens
            $regs = array(
              '/{NAME_ADH}/',
              '/{DEADLINE}/',
              '/{COMMENT}/'
            );

            $replacements = array(
                $adh->sname,
                custom_html_entity_decode($contrib->end_date),
                custom_html_entity_decode($contrib->info)
            );

            $body = preg_replace(
                $regs,
                $replacements,
                $mtxt->tbody
            );

            $mail = new GaletteMail();
            $mail->setSubject($mtxt->tsubject);
            /** TODO: only super-admin is contacted here. We should send a message to all admins, or propose them a chekbox if they don't want to get bored */
            $mail->setRecipients(
                array(
                    $preferences->pref_email_newadh => str_replace('%asso', $preferences->pref_name, _T("%asso Galette's admin"))
                )
            );

            $mail->setMessage($body);
            $sent = $mail->send();

            if ( $sent ) {
                    $hist->add(
                        preg_replace(
                            array('/%name/', '/%email/'),
                            array($adh->sname, $adh->email),
                            _T("Mail sent to admin for user %name (%email)")
                        )
                    );
            } else {
                $txt = preg_replace(
                    array('/%name/', '/%email/'),
                    array($adh->sname, $adh->email),
                    _T("A problem happened while sending to admin notification for user %name (%email) contribution")
                );
                $hist->add($txt);
                $error_detected[] = $txt;
            }
        }

        if ( count($error_detected) == 0 ) {
            if ( $contrib->isTransactionPart() &&
                $contrib->transaction->getMissingAmount() > 0
            ) {
                $url = 'ajouter_contribution.php?trans_id=' .
                    $contrib->transaction->id . '&id_adh=' .
                    $contrib->member;
            } else {
                $url = 'gestion_contributions.php?id_adh=' . $contrib->member;
            }
            header('location: ' . $url);
        }
    }

    /** TODO: remove */
    if ( !isset($contribution['duree_mois_cotis'])
        || $contribution['duree_mois_cotis'] == ''
    ) {
        // On error restore entered value or default to display the form again
        if ( isset($_POST['duree_mois_cotis'])
            && $_POST['duree_mois_cotis'] != ''
        ) {
            $contribution['duree_mois_cotis'] = $_POST['duree_mois_cotis'];
        } else {
            $contribution['duree_mois_cotis'] = $preferences->pref_membership_ext;
        }
    }
}  else { //$_POST['valid']
    if ( !isset($contrib->id) ) {
        // initialiser la structure contribution à vide (nouvelle contribution)
        $contribution['duree_mois_cotis'] = $preferences->pref_membership_ext;
    } else {
        // dynamic fields
        $contribution['dyn'] = get_dynamic_fields(
            'contrib',
            $contribution["id_cotis"],
            false
        );
    }
}

// template variable declaration
$tpl->assign('required', $required);
$tpl->assign('disabled', $disabled);
$tpl->assign('data', $contribution); //TODO: remove
$tpl->assign('contribution', $contrib);
$tpl->assign('error_detected', $error_detected);
$tpl->assign('type_selected', $type_selected);
$tpl->assign('adh_selected', $id_adh);

// contribution types
$type_cotis_options = ContributionsTypes::getList(
    ($type_selected == 1 && $id_adh != '') ? $contrib->isCotis() : null
);
$tpl->assign('type_cotis_options', $type_cotis_options);

// members
$varslist = new VarsList();
$members = Members::getList(true);
if ( count($members) == 0 ) {
    $adh_options = array('' => _T("You must first register a member"));
} else {
    foreach ( $members as $member ) {
        $adh_options[$member->id] = $member->sname;
    }
}

$tpl->assign('adh_options', $adh_options);
$tpl->assign('require_calendar', true);

$tpl->assign('pref_membership_ext', $cotis_extension ? $preferences->pref_membership_ext : '');  //TODO: remove and replace with $contrib specific property

// - declare dynamic fields for display
$dynamic_fields = prepare_dynamic_fields_for_display(
    'contrib',
    $contribution['dyn'],
    array(),
    1
);
$tpl->assign('dynamic_fields', $dynamic_fields);

// page generation
$content = $tpl->fetch('ajouter_contribution.tpl');
$tpl->assign('content', $content);
$tpl->display('page.tpl');
?>

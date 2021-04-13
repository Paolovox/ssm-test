<?php

use \ottimis\phplibs\Utils;
use \ottimis\phplibs\dataBase;
use \ottimis\phplibs\Logger;


class Auth
{

    private $users_table = 'oauth_users';
    private $users_data_table = 'oauth_users';

    const FRONTEND_URL = 'https://login.unidata.it/reset-password?';

    const USER_PASSWORD_WRONG = array("success" => false, "errorMessage" => "Utente non trovato o password errata.", "errorCode" => '01');
    const PASSWORD_EXPIRED = array("success" => false, "errorMessage" => "La tua password è scaduta, devi rinnovarla.", "errorCode" => '02');
    const LOCKEDOUT = array("success" => false, "errorMessage" => "Il tuo account è stato bloccato.", "errorCode" => '03');
    const USER_NOT_FOUND = array("success" => false, "errorMessage" => "Utente non trovato.", "errorCode" => '04');
    const TEMP_CODE_NOT_FOUND = array("success" => false, "errorMessage" => "Richiesta errata.", "errorCode" => '05');
    const PASSWORD_NOT_MATCH = array("success" => false, "errorMessage" => "Le password non corrispondono.", "errorCode" => '06');
    const PASSWORD_POLICY = array("success" => false, "errorMessage" => "La password non soddisfa i criteri richiesti.", "errorCode" => '07');
    const PASSWORD_POLICY_SAME = array("success" => false, "errorMessage" => "La password non può essere uguale ad una delle precedenti.", "errorCode" => '08');

    public function __construct($users_table = '', $users_data_table = '')  {
        if ($users_table != '') {
            $this->users_table = $users_table;
        }
        if ($users_data_table != '') {
            $this->users_data_table = $users_data_table;
        }
    }

    public function verifyPassword($email, $password)
    {
        $utils = new Utils();
        $logger = new Logger();
        $arSql = array(
            "log" => true,
            "select" => ["id_utente", "password", "last_password_change", "failed_logins", "lockout", "DATEDIFF(now(), last_password_change) as last_password_interval"],
            "from" => $this->users_table,
            "where" => [
                [
                    "field" => "email",
                    "value" => $email,
                    "operatorAfter" => "AND"
                ],
                [
                    "field" => "cancellato",
                    "value" => 0
                ]
            ]
        );
        $arrSql = $utils->dbSelect($arSql)['data'][0];

        if ($arrSql['lockout']) {
            $logger->warning('Utente ' . $email . ' bloccato all\'accesso per account disabilitato');
            return self::LOCKEDOUT;
        }

        $ok = false;
        if (substr($arrSql['password'], 0, 5) == '{MD5}') {
            $final_password = '{MD5}' . md5($password);
            if (strcasecmp($final_password, $arrSql['password']) === 0) {
                $ok = true;
            }
        } else  {
            if (password_verify($password, $arrSql['password']))    {
                $ok = true;
            }
        }
        if ($ok) {
            $ar = array(
                "failed_logins" => 0
            );
            $retUpdate = $utils->dbSql(false, $this->users_table, $ar, "email", $email);
            if (!$retUpdate['success']) {
                $logger->error('Non sono riuscito ad azzerare il contatore dei failed_logins per la mail: ' . $email, 'AUTH01');
            }
            if ($arrSql['last_password_interval'] > 90) {
                return self::PASSWORD_EXPIRED;
            }
            return array(
                "success" => true,
                "idUtente" => $arrSql['id_utente']
            );
        } else {
            if ($arrSql['failed_logins'] >= 4)  {
                $ar = array(
                    "lockout" => 1
                );    
            } else {
                $ar = array(
                    "failed_logins" => $arrSql['failed_logins'] + 1
                );
            }
            $retUpdate = $utils->dbSql(false, $this->users_table, $ar, "email", $email);
            if (!$retUpdate['success']) {
                $logger->error('Non sono riuscito ad aumentare il contatore dei failed_logins per la mail: ' . $email, 'AUTH02');
            }
            return self::USER_PASSWORD_WRONG;
        }
    }

    function getUserByEmail($email) {
        $utils = new Utils();
        $arSql = array(
            "log" => true,
            "select" => ["id_utente", "fullname", "oud.email", "last_password_change", "lockout", "oud.first_name"],
            "from" => $this->users_table,
            "join" => [
                [
                    "oauth_users_data oud ",
                    "oud.user_id=id_utente"
                ]
                ],
            "where" => [
                [
                    "field" => "oud.email",
                    "value" => $email,
                    "operatorAfter" => "AND"
                ],
                [
                    "field" => "cancellato",
                    "value" => 0
                ]
            ]
        );
        $arrSql = $utils->dbSelect($arSql)['data'][0];

        if ($arrSql['id_utente'] > 0) {
            if ($arrSql['lockout']) {
                return self::LOCKEDOUT;
            }
            return array(
                "success" => true,
                "idUtente" => $arrSql['id_utente'],
                "email" => $arrSql['email'],
                "nome" => isset($arrSql['fullname']) ? $arrSql['fullname'] : $arrSql['first_name']
            );
        } else {
            return self::USER_NOT_FOUND;
        }
    }

    function sendCreateEmail($name, $email) {
        $utils = new Utils();
        $tempPassword = rand(100000, 900000);
        $ar = array(
            "temp_code" => $tempPassword,
            "temp_code_expire" => "NOW() + INTERVAL 24 HOUR"
        );
        $retInsert = $utils->dbSql(false, $this->users_table, $ar, "email", $email);
        if ($retInsert['success'])  {
            $url = self::FRONTEND_URL . 'code=' . $tempPassword . '&email=' . $email;
            $replace = array('{{name}}', '{{action_url}}');
            $with = array($name, $url);

            ob_start();
            include('templates/welcome_email.html');
            $ob = ob_get_clean();

            $html = str_replace($replace, $with, $ob);

            $retEmail = sendMail($email, 'Crea password - Unidata', $html);
            if ($retEmail)  {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    function sendResetEmail($name, $email) {
        $utils = new Utils();
        $tempPassword = rand(100000, 900000);
        $ar = array(
            "temp_code" => $tempPassword,
            "temp_code_expire" => "NOW() + INTERVAL 24 HOUR"
        );
        $retInsert = $utils->dbSql(false, $this->users_table, $ar, "email", $email);
        if ($retInsert['success'])  {
            $url = self::FRONTEND_URL . 'code=' . $tempPassword . '&email=' . $email;
            $replace = array('{{name}}', '{{action_url}}');
            $with = array($name, $url);

            ob_start();
            include('templates/reset_email.html');
            $ob = ob_get_clean();

            $html = str_replace($replace, $with, $ob);

            $retEmail = sendMail($email, 'Reset password - Unidata', $html);
            if ($retEmail)  {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    function updatePassword( $email, $code, $password, $passwordRepeat) {
        $utils = new Utils();

        if (!$this->checkPasswordReset($email, $code)['success'])    {
            return self::TEMP_CODE_NOT_FOUND;
        }

        if ($password != $passwordRepeat)   {
            return self::PASSWORD_NOT_MATCH;
        }

        if (preg_match("/(?!.*(.)\1\1.*)(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[$@$!%*?&])[A-Za-z\d$@$!%*?&].{8,}/", $password) != true)  {
            return self::PASSWORD_POLICY;
        }

        return $this->_updatePassword($email, $password);
    }

    function checkPasswordReset( $email, $code )    {
        $utils = new Utils();
        $db = new dataBase();

        $sql = sprintf("SELECT temp_code
            FROM %s
            WHERE email='%s' AND temp_code_expire > now()", $this->users_table, $db->real_escape_string($email));
        $db->query($sql);

        $rec = $db->fetchassoc();

        if ($code != $rec['temp_code']) {
            return self::TEMP_CODE_NOT_FOUND;
        }

        return array(
            "success" => true
        );
    }

    function getOldPassword($email) {
        $utils = new Utils();

        $arSql = array(
            "select" => ["password", "password_history"],
            "from" => $this->users_table,
            "where" => [
                [
                    "field" => "email",
                    "value" => $email
                ]
            ]
        );
        
        $arrSql = $utils->dbSelect($arSql);

        return array(
            "old" => json_decode($arrSql['data'][0]['password_history']),
            "last" => $arrSql['data'][0]['password']
        );
    }

    function updatePushPassword($email, $password, $oldPasswords)   {
        $utils = new Utils();

        $oldPassword = array_slice($oldPasswords['old'], -5);
        $oldPassword[] = $oldPasswords['last'];

        $oldHashed = '{MD5}' . md5($password);
        $password = password_hash($password, PASSWORD_DEFAULT);        
        if ( array_search($oldHashed, $oldPassword) !== false || array_search($password, $oldPassword) !== false )  {
            return self::PASSWORD_POLICY_SAME;
        }
        $ar = array(
            "password" => $password,
            "password_history" => json_encode($oldPassword),
            "last_password_change" => 'now()'
        );
        $retUpdate = $utils->dbSql(false, $this->users_table, $ar, "email", $email);

        return array(
            "success" => $retUpdate['success']
        );
    }

    function _updatePassword($email, $password) {
        // Prendo le vecchie password e l'ultima
        $oldPasswords = $this->getOldPassword($email);
        return $this->updatePushPassword($email, $password, $oldPasswords);
    }

    // Ritorna true se la data passata come parametro è minore di oggi.
    function passwordExpired($date)
    {
        $today = new DateTime();
        $date = new DateTime($date);
        $interval = $today->diff($date);
        return $interval->format("%a") > 90 ? true : false;
    }

}

?>
<?php

use \ottimis\phplibs\Logger;
use \ottimis\phplibs\Utils;
use \ottimis\phplibs\dataBase;
use \ottimis\phplibs\UUID;
use \ottimis\phplibs\Auth;

function loginUnicatt( $user ) {

    $utils = new Utils();
    $uuid = new UUID();
    $log = new Logger();
    $auth = new Auth("TOKENOTTIMIS", "func", "idRole", "scopes", "extra");

    $userData = utente_data_get_cf($user['codice_fiscale']);
    if ($userData['id'] != '')  {
        $jwt = $auth->tokenRefresh($userData['idruolo'], $userData);
    }
    // else {
    //     $user['id'] = $uuid->v4();
    //     $user['date_create'] = "now()";
    //     $user['date_update'] = "now()";
    //     $user['idstatus'] = "1";
    //     $ret = $utils->dbSql(true, "ssm_utenti", $user);
    //     $jwt = $auth->tokenRefresh(8, $user);
    // }

    return $jwt;
}

?>
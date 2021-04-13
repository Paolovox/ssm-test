<?php


// Slim framework
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Routing\RouteCollectorProxy as RouteCollectorProxy;
use Slim\Exception\HttpNotFoundException as HttpNotFoundException;
use Slim\Factory\AppFactory;

use \ottimis\phplibs\Logger;
use \ottimis\phplibs\Utils;
use \ottimis\phplibs\dataBase;
use \ottimis\phplibs\Auth;
use \ottimis\phplibs\UUID;

// Import
$app->group('/import', function (RouteCollectorProxy $groupImport) use ($auth) {
    $groupImport->get('/federated', function (Request $request, Response $response) use ($auth) {
        $db = new dataBase();
        $db2 = new dataBase();

        // Lista tabelle da Federare
        $tables = [
        "ssm.ssm_atenei",
        "ssm.ssm_aziende",
        "ssm.ssm_pds",
        "ssm.ssm_pds_ambiti_disciplinari",
        "ssm.ssm_pds_aree",
        "ssm.ssm_pds_attivita_formative",
        "ssm.ssm_pds_classi",
        "ssm.ssm_pds_coorti",
        "ssm.ssm_pds_coorti_contatori",
        "ssm.ssm_pds_coorti_export",
        "ssm.ssm_pds_registrazioni_filtri",
        "ssm.ssm_presidi",
        "ssm.ssm_presidi_atenei",
        "ssm.ssm_registrazioni_attivita",
        "ssm.ssm_registrazioni_attivita_tipologie",
        "ssm.ssm_registrazioni_combo",
        "ssm.ssm_registrazioni_combo_items",
        "ssm.ssm_registrazioni_filtri",
        "ssm.ssm_registrazioni_registrazioni_tipi",
        "ssm.ssm_registrazioni_schema",
        "ssm.ssm_registrazioni_stato",
        "ssm.ssm_scuole",
        "ssm.ssm_scuole_atenei",
        "ssm.ssm_scuole_attivita_np",
        "ssm.ssm_scuole_attivita_np_dati",
        "ssm.ssm_scuole_attivita_np_tipi_campo",
        "ssm.ssm_scuole_pds_insegnamenti",
        "ssm.ssm_scuole_pds_obiettivi",
        "ssm.ssm_scuole_registrazioni_np_calendario",
        "ssm.ssm_scuole_settori_scientifici",
        "ssm.ssm_scuole_unita",
        "ssm.ssm_settori_scientifici",
        "ssm.ssm_specializzando_status",
        "ssm_tipologia_attivita",
        "ssm.ssm_unita_operative",
        "ssm.ssm_unita_tipologie",
        "ssm.ssm_utenti_ruoli",
        "ssm_valutazioni_tutor"
      ];
        $from = "ssm";
        $to = "ssm_unicatt";

        $sql = sprintf("show tables FROM $from");
        $db->query($sql);
        while ($table = $db->fetcharray()) {
            $t = $table[0];
            echo $t;
            echo "<br>";
            if (!in_array($t, $tables)) {
                continue;
            }
            $sqlDelete = "DROP TABLE IF EXISTS $to.$t";
            $db2->query($sqlDelete);
            echo $db2->error();
            echo "<br>";
            echo "<br>";

            $sqlCreate = "show create table $from.$t";
            $db2->query($sqlCreate);
            $create = $db2->fetcharray()[1];
            $create = str_replace("MyISAM", "FEDERATED", $create);
            $create = str_replace("InnoDB", "FEDERATED", $create);
            $create = str_replace("`$t`", "$to.`$t`", $create);
            $create .= " CONNECTION='mysql://ssm:\$cu2slq&l&\$kk98IJye%@127.0.0.1:3306/$from/$t'";
            $db2->query($create);
        }

        $response->getBody()->write("FINE");
        return $response
        ->withStatus(200)
        ->withHeader('Content-Type', 'text/plain');
    });

    // CREATE TABLE ssm_unicatt.`aziende_copy1` ( `id` int(11) NOT NULL AUTO_INCREMENT, `azienda_out` varchar(60) DEFAULT NULL, `idazienda_ext` varchar(36) DEFAULT NULL, PRIMARY KEY (`id`) ) ENGINE=InnoDB AUTO_INCREMENT=122 DEFAULT CHARSET=utf8 CONNECTION='mysql://ssm:$cu2slq&l&$kk98IJye%@127.0.0.1:3306/ssm_unicatt/aziende_copy1'FINE
    $groupImport->get('/pwreset', function (Request $request, Response $response) use ($auth) {
        $db = new dataBase();
        $Utils = new Utils();

        $hashedPassword = password_hash("2.daCs£.a3!", PASSWORD_DEFAULT);
        $sql = sprintf("UPDATE ssm.ssm_utenti SET password='%s' WHERE password='NOAUTH'", $db->real_escape_string($hashedPassword));
        $db->query($sql);

        $response->getBody()->write("FINE");
        return $response
        ->withStatus(200)
        ->withHeader('Content-Type', 'text/plain');
    });

    $groupImport->get('/studenti', function (Request $request, Response $response) use ($auth) {
        $db = new dataBase();
        $Utils = new Utils();

        $base = array(
            "id" => "a621bcc9-dd77-4fa6-91f4-3c88acd4fa0b",
            "idateneo" => "65edb001-b9be-4da9-ae28-2e1b96f53be8",
            "idscuola" => "a621bcc9-dd77-4fa6-91f4-3c88acd4fa0b"
        );

        $sql = sprintf("SELECT *
          FROM ssm_unimi_import_scuole.SPECIALIZZANDO ut_in
          LEFT JOIN ssm_utenti ut ON ut.id=concat('UNIMI-S-',ut_in.CODICE_FISCALE)
          WHERE FLAG_STUDENTE=1 AND ATTIVO=1
          AND ut.id is null");
        $db->query($sql);
        echo $sql;

        echo $db->error();

        while ($rec = $db->fetchassoc()) {
            echo "<br>" . $rec['COGNOME'];

            $ins = array(
                "id" => "UNIMI-S-" . $rec['CODICE_FISCALE'],
                "nome" => $rec['NOME'],
                "cognome" => $rec['COGNOME'],
                "genere" => $rec['SESSO'],
                "matricola" => $rec['MATRICOLA'],
                "email" => $rec['EMAIL'],
                "codice_fiscale" => $rec['CODICE_FISCALE'],
                "residenza_indirizzo" => $rec['INDIRIZZO_RESIDENZA'],
                "residenza_citta" => $rec['LUOGO_RESIDENZA'],
                "telefono" => $rec['TELEFONO'],
                "data_nascita" => substr($rec['DATA_NASCITA'], 0, 10),
                "luogo_nascita" => $rec['LUOGO_NASCITA'],
                "residenza_cap" => $rec['CAP_RESIDENZA'],
                "idstatus" => 1,
                "date_create" => "now()",
                "date_update" => "now()",
                "password" => "NOAUTH"
            );

            $ret = $Utils->dbSql(true, "ssm.ssm_utenti", $ins, "", "");
            print_r($ret);
            if ($ret['success'] != 1) {
                $db->error();
                print_r($ret);
                exit;
            }

            echo "OK2";
            //print_r( $ins );

            $ins_ruolo = array(
                "idutente" => $ins['id'],
                "idruolo" => 11,
                "idateneo" => $base['idateneo'],
                "idscuola" => $base['idscuola'],
                "idstatus" => 1,
                "date_create" => "now()",
                "date_update" => "now()"
            );

            $ret = $Utils->dbSql(true, "ssm.ssm_utenti_ruoli_lista", $ins_ruolo, "", "");
            if ($ret['success'] != 1) {
                print_r($ret);
                exit;
            }

            //print_r( $ins_ruolo );
        }

        //echo $db->error();

        //TODO:completare integrazione
        $db->query("TRUNCATE TABLE nomos_libretto.studenti_cdl");
        $sql = "INSERT INTO nomos_libretto.studenti_cdl
          (idstudente,idcdl)
          SELECT concat('UNIMI-S-', ut_in.CODICE_FISCALE), cdl.id
          FROM ssm_unimi_import.SPECIALIZZANDO ut_in
          LEFT JOIN ssm.ssm_utenti ut ON ut.id=concat('UNIMI-S-',ut_in.CODICE_FISCALE)
					LEFT JOIN nomos_libretto.corsi_di_laurea cdl ON cdl.codice_corso=ut_in.COD_CDLULT
          WHERE FLAG_STUDENTE=1 AND ATTIVO=1";


        $response->getBody()->write("FINE");
        return $response
        ->withStatus(200)
        ->withHeader('Content-Type', 'application/json');
    });



    $groupImport->get('/tutor', function (Request $request, Response $response) use ($auth) {
        $db = new dataBase();
        $Utils = new Utils();


        $base = array(
            "id" => "a621bcc9-dd77-4fa6-91f4-3c88acd4fa0b",
            "idateneo" => "65edb001-b9be-4da9-ae28-2e1b96f53be8",
            "idscuola" => "a621bcc9-dd77-4fa6-91f4-3c88acd4fa0b"
        );



        // $sql = sprintf("SELECT
        //         DISTINCT rt.CF_TUTOR
        //     FROM
        //         ssm_unimi_import.ROTAZIONE_TUTOR rt
        //         LEFT JOIN ssm_unimi_import.ANAGRAFICA_RUOLO ar ON ar.COD_FISCALE = rt.CF_TUTOR AND ar.ID_RUOLO = 5 AND ar.COD_SCUOLA = '000'
        //         LEFT JOIN ssm_unimi_import.ANAGRAFICA an ON an.COD_FISCALE = rt.CF_TUTOR
        //     WHERE
        //         ID_RUOLO = 5
        //         AND rt.COD_SCUOLA = '000'
        //         AND rt.ATTIVO =1");
        $sql = sprintf("SELECT ar.*, an.* from ssm_unimi_import.ANAGRAFICA_RUOLO ar
            LEFT JOIN ssm_unimi_import.ANAGRAFICA an ON an.COD_FISCALE=ar.COD_FISCALE
            LEFT JOIN ssm_utenti ut ON ut.id=concat('UNIMI-T-',ar.COD_FISCALE)
            WHERE ID_RUOLO=5 AND COD_SCUOLA='000' AND ATTIVO=1
            AND ut.id is null");

        $sql = sprintf("SELECT * from ssm_unimi_import.ANAGRAFICA_RUOLO ar
          LEFT JOIN ssm_unimi_import.ANAGRAFICA an ON an.COD_FISCALE=ar.COD_FISCALE
          LEFT JOIN ssm.ssm_utenti ut ON ut.id=concat('UNIMI-T-',ar.COD_FISCALE)
          WHERE ID_RUOLO=5 AND COD_SCUOLA='000' AND ATTIVO=1
            AND ut.id is null");

        $db->query($sql);
        echo $sql;
        while ($rec = $db->fetchassoc()) {
            echo "<br>" . $rec['COGNOME'];

            $ins = array(
                "id" => "UNIMI-T-" . $rec['COD_FISCALE'],
                "nome" => $rec['NOME'],
                "cognome" => $rec['COGNOME'],
                "genere" => $rec['SESSO'],
                "matricola" => $rec['MATRICOLA'],
                "email" => trim($rec['MAIL'])=="" ? $rec['MAIL'] : $rec['MAIL_ALTERNATIVA'],
                "codice_fiscale" => $rec['COD_FISCALE'],
                "residenza_indirizzo" => $rec['INDIRIZZO_RESIDENZA'],
                "residenza_citta" => $rec['LUOGO_RESIDENZA'],
                "telefono" => $rec['TELEFONO'],
                "luogo_nascita" => $rec['LUOGO_NASCITA'],
                "residenza_cap" => $rec['CAP_RESIDENZA'],
                "idstatus" => 1,
                "date_create" => "now()",
                "date_update" => "now()",
                "password" => "NOAUTH"
            );

            if ($rec['DATA_NASCITA'] != "") {
                $ins['data_nascita'] = $rec['DATA_NASCITA'];
            }

            $ret = $Utils->dbSql(true, "ssm.ssm_utenti", $ins, "", "");
            print_r($ret);
            if ($ret['success'] != 1) {
                $db->error();
                print_r($ret);
                exit;
            }

            echo "OK2";
            //print_r( $ins );


            $tutor = getReteTutor($rec['COD_FISCALE']);
            foreach ($tutor as $k => $v) {
                $ins_ruolo = array(
                    "idutente" => $ins['id'],
                    "idruolo" => 7,
                    "idateneo" => $base['idateneo'],
                    "idscuola" => $base['idscuola'],
                    "idpresidio" => $v['idpresidio'],
                    "idunita" => $v['idunita'],
                    "idstatus" => 1,
                    "date_create" => "now()",
                    "date_update" => "now()"
                );

                $ret = $Utils->dbSql(true, "ssm.ssm_utenti_ruoli_lista", $ins_ruolo, "", "", true);
                if ($ret['success'] != 1) {
                    print_r($ret);
                    $db->error();
                    exit;
                }
            }

            //print_r( $ins_ruolo );
        }

        //echo $db->error();

        $response->getBody()->write("FINE");
        return $response
        ->withStatus(200)
        ->withHeader('Content-Type', 'application/json');
    });

    $groupImport->get('/tutor/studenti', function (Request $request, Response $response) use ($auth) {
        $db = new dataBase();
        $Utils = new Utils();
        $uuid = new UUID();


        $base = array(
            "id" => "a621bcc9-dd77-4fa6-91f4-3c88acd4fa0b",
            "idateneo" => "65edb001-b9be-4da9-ae28-2e1b96f53be8",
            "idscuola" => "a621bcc9-dd77-4fa6-91f4-3c88acd4fa0b"
        );

        $sql = sprintf("SELECT  CONCAT('UNIMI-T-', rt.CF_TUTOR) as idtutor,
        CONCAT('UNIMI-S-', s.CODICE_FISCALE) as idstudente,
        rt.ID_AREA_TIROCINIO as idarea,
        rt.DATA_INIZIO as data_inizio, rt.DATA_FINE as data_fine
        FROM ssm_unimi_import.ROTAZIONE_TUTOR rt
        LEFT JOIN ssm_unimi_import.SPECIALIZZANDO s ON s.MATRICOLA = rt.MATRICOLA
        LEFT JOIN ssm.ssm_studenti_tutor st ON st.idstudente=CONCAT('UNIMI-S-', s.CODICE_FISCALE)
      AND st.idtutor=CONCAT('UNIMI-T-', rt.CF_TUTOR)
      AND st.idarea=rt.ID_AREA_TIROCINIO
      AND st.data_inizio = rt.data_inizio
      AND st.data_fine=rt.data_fine
    WHERE rt.CF_TUTOR IS NOT NULL
    AND s.CODICE_FISCALE IS NOT NULL
    AND s.FLAG_STUDENTE = 1
    AND s.ATTIVO = 1
    AND rt.ATTIVO=1
    AND st.idtutor is null
    order by CONCAT('UNIMI-T-', rt.CF_TUTOR)");
        $db->query($sql);
        echo $sql;
        echo $db->error();
        while ($rec = $db->fetchassoc()) {
            echo ".";
            $rec['id'] = $uuid->v4();
            $rec['data_inizio'] = substr($rec['data_inizio'], 0, 10);
            $rec['data_fine'] = substr($rec['data_fine'], 0, 10);
            print_r($rec);
            $ret = $Utils->dbSql(true, "ssm.ssm_studenti_tutor", $rec, "", "");
            if ($ret['success'] != 1) {
                print_r($ret);
                exit;
            }
        }

        $response->getBody()->write("FINE");
        return $response
        ->withStatus(200)
        ->withHeader('Content-Type', 'application/json');
    });


    function ateneoGet($idAteneo)
    {
        $Utils = new Utils();
        $arSql = array(
        "select" => ["*"],
        "from" => "ssm.ssm_atenei",
        "where" => [
          [
            "field" => "id",
            "value" => $idAteneo
          ]
        ]
      );

        $arrSql = $Utils->dbSelect($arSql);
        return $arrSql['data'][0];
    }

    $groupImport->get('/associazione/scuole', function (Request $request, Response $response) use ($auth) {
        $db = new dataBase();
        $Utils = new Utils();
        $log = new Logger();

        $p = $request->getQueryParams();
        $page = $p['page'];
        $page = $page * 15;

        $table = "ssm.ssm_scuole";
        if ($p['idateneo'] != '') {
            $ateneo = ateneoGet($p['idateneo']);
            $dbName = $ateneo['db_name'];
            $table = "$dbName" . "_scuole";
            putenv("DB_NAME=$dbName");
        }

        $sql = sprintf("SELECT SQL_CALC_FOUND_ROWS
                        DISTINCT
                            s.scuola_out,
                            s.idscuola_ext,
                            ss.nome_scuola as scuola,
                            ss.id as idscuola,
                            sas.idscuola as idscuola_assoc
                        FROM
                            ssm_import_strutture.%s s
                        LEFT JOIN ssm.ssm_scuole ss ON TRIM(LOWER(ss.nome_scuola)) like CONCAT( '%%', TRIM(LOWER(s.scuola_out)), '%%' )
                          AND ss.idstatus = 1
                        LEFT JOIN ssm_associazioni_scuole sas ON sas.idscuola_ext= s.idscuola_ext
                        WHERE
                            s.scuola_out IS NOT NULL AND s.scuola_out != ''
                        ORDER BY
                            s.scuola_out
                        LIMIT %d, 15", $table, $page);
        $log->log($sql);
        $db->query($sql);
        while ($rec = $db->fetchassoc()) {
            if ($rec['idscuola_assoc'] != '') {
                $rec['idscuola'] = $rec['idscuola_assoc'];
            }
            $ar[] = $rec;
        }
        $db->query("SELECT FOUND_ROWS()");
        $res['total'] = intval($db->fetcharray()[0]);


        $res['scuole_all'] = $ar;
        $where = "idstatus=1";
        $arCombo = array( "table" => "ssm.ssm_scuole", "value" => "id", "text" => "nome_scuola", "order" => "nome_scuola", "where" => $where );
        $res['scuole'] = $Utils->_combo_list($arCombo, true, "");
        $where = "idstatus=1";
        $arCombo = array( "table" => "ssm.ssm_atenei", "value" => "id", "text" => "nome_ateneo", "order" => "nome_ateneo", "where" => $where );
        $res['atenei'] = $Utils->_combo_list($arCombo, true, "");

        $response->getBody()->write(json_encode($res));
        return $response
        ->withStatus(200)
        ->withHeader('Content-Type', 'application/json');
    });


    $groupImport->put('/associazione/scuole', function (Request $request, Response $response) use ($auth) {
        $db = new dataBase();
        $Utils = new Utils();

        $p = $request->getQueryParams();
        if ($p['idateneo'] != '') {
            $ateneo = ateneoGet($p['idateneo']);
            $dbName = $ateneo['db_name'];
            putenv("DB_NAME=$dbName");
        }
        $body = json_decode($request->getBody(), true);

        foreach ($body as $value) {
            unset($value['scuola_out']);
            unset($value['scuola']);
            unset($value['idscuola_assoc']);
            // $value['idateneo'] = 1;
            $Utils->dbSql(true, "ssm_associazioni_scuole", $value, "", "");
        }

        $response->getBody()->write(json_encode($res));
        return $response
        ->withStatus(200)
        ->withHeader('Content-Type', 'application/json');
    });

    $groupImport->get('/associazione/aziende', function (Request $request, Response $response) use ($auth) {
        $db = new dataBase();
        $Utils = new Utils();
        $log = new Logger();

        $page = $request->getQueryParams()['page'];
        $page = $page * 15;

        $sql = sprintf("SELECT SQL_CALC_FOUND_ROWS
                        DISTINCT
                            a.azienda_out,
                            a.idazienda_ext,
                            sa.nome as azienda,
                            sa.id as idazienda,
                            saa.idazienda as idazienda_assoc
                        FROM
                            ssm_import_strutture.aziende a
                        LEFT JOIN ssm.ssm_aziende sa ON TRIM(LOWER(sa.nome)) like CONCAT( '%%', TRIM(LOWER(a.azienda_out)), '%%' )
                          AND sa.idstatus = 1
                        LEFT JOIN ssm_associazioni_aziende saa ON saa.idazienda_ext= a.idazienda_ext
                        WHERE
                            a.azienda_out IS NOT NULL AND a.azienda_out != ''
                        ORDER BY
                            a.azienda_out
                        LIMIT %d, 15", $page);
        $log->log($sql);
        $db->query($sql);
        while ($rec = $db->fetchassoc()) {
            if ($rec['idazienda_assoc'] != '') {
                $rec['idazienda'] = $rec['idazienda_assoc'];
            }
            $ar[] = $rec;
        }
        $db->query("SELECT FOUND_ROWS()");
        $res['total'] = intval($db->fetcharray()[0]);


        $res['aziende_all'] = $ar;
        $where = sprintf("idstatus=1", $idazienda);
        $arCombo = array( "table" => "ssm.ssm_aziende", "value" => "id", "text" => "nome", "order" => "nome", "where" => $where );
        $res['aziende'] = $Utils->_combo_list($arCombo, true, "");

        $response->getBody()->write(json_encode($res));
        return $response
        ->withStatus(200)
        ->withHeader('Content-Type', 'application/json');
    });

    $groupImport->put('/associazione/aziende', function (Request $request, Response $response) use ($auth) {
        $db = new dataBase();
        $Utils = new Utils();

        $body = json_decode($request->getBody(), true);

        foreach ($body as $value) {
            unset($value['azienda_out']);
            unset($value['azienda']);
            unset($value['idazienda_assoc']);
            $value['iduniversita'] = 1;
            $Utils->dbSql(true, "ssm_associazioni_aziende", $value, "", "");
        }

        $response->getBody()->write(json_encode($res));
        return $response
        ->withStatus(200)
        ->withHeader('Content-Type', 'application/json');
    });

    $groupImport->get('/associazione/presidi/aziende', function (Request $request, Response $response) use ($auth) {
        $db = new dataBase();
        $Utils = new Utils();

        $sql = "SELECT
                DISTINCT saa.idazienda_ext,
                saa.idazienda,
                a.azienda_out as text
                FROM ssm_associazioni_aziende saa
                LEFT JOIN ssm_import_strutture.aziende a ON a.idazienda_ext = saa.idazienda_ext";
        $db->query($sql);
        while ($rec = $db->fetchassoc()) {
            $ar[] = $rec;
        }

        $res['aziende'] = $ar;

        $response->getBody()->write(json_encode($res));
        return $response
        ->withStatus(200)
        ->withHeader('Content-Type', 'application/json');
    });

    $groupImport->get('/associazione/presidi', function (Request $request, Response $response) use ($auth) {
        $db = new dataBase();
        $Utils = new Utils();
        $log = new Logger();

        $idazienda = $request->getQueryParams()['idazienda'];
        $idaziendaExt = $request->getQueryParams()['idazienda_ext'];
        $page = $request->getQueryParams()['page'];
        $page = $page * 15;

        $sql = sprintf("SELECT SQL_CALC_FOUND_ROWS
                    DISTINCT
                    p.presidio_out,
                    p.idpresidio_ext,
                    sp.nome as presidio,
                    sp.id as idpresidio,
                    sap.idpresidio as idpresidio_assoc
                FROM
                    ssm_import_strutture.presidi p
                LEFT JOIN ssm.ssm_presidi sp ON
                   sp.idazienda='%s' AND
                      TRIM(LOWER(sp.nome)) like CONCAT( '%%', TRIM(LOWER(p.presidio_out)), '%%' ) AND sp.idstatus=1
                LEFT JOIN ssm_associazioni_presidi sap ON sap.idpresidio_ext = p.idpresidio_ext AND sap.idazienda_ext = p.idazienda_ext
                WHERE
                    p.idazienda_ext = %d AND p.presidio_out IS NOT NULL AND p.presidio_out != ''
                ORDER BY
                    p.presidio_out", $db->real_escape_string($idazienda), $idaziendaExt);
        $log->log($sql);
        $db->query($sql);
        while ($rec = $db->fetchassoc()) {
            $rec['idazienda_ext'] = $idaziendaExt;
            $rec['idazienda'] = $idazienda;
            if ($rec['idpresidio_assoc'] != '') {
                $rec['idpresidio'] = $rec['idpresidio_assoc'];
            }
            $ar[] = $rec;
        }

        $res['presidi_all'] = $ar;

        $where = sprintf("idazienda = '%s' AND idstatus=1", $idazienda);
        $arCombo = array( "table" => "ssm.ssm_presidi", "value" => "id", "text" => "nome", "order" => "nome", "where" => $where );
        $res['presidi'] = $Utils->_combo_list($arCombo, $where, true);

        $response->getBody()->write(json_encode($res));
        return $response
        ->withStatus(200)
        ->withHeader('Content-Type', 'application/json');
    });

    $groupImport->put('/associazione/presidi', function (Request $request, Response $response) use ($auth) {
        $db = new dataBase();
        $Utils = new Utils();

        $body = json_decode($request->getBody(), true);

        foreach ($body as $value) {
            unset($value['presidio_out']);
            unset($value['presidio']);
            unset($value['idpresidio_assoc']);
            $value['iduniversita'] = 1;
            $Utils->dbSql(true, "ssm_associazioni_presidi", $value, "", "");
        }

        $response->getBody()->write(json_encode($res));
        return $response
        ->withStatus(200)
        ->withHeader('Content-Type', 'application/json');
    });



    $groupImport->get('/associazione/unita/presidi', function (Request $request, Response $response) use ($auth) {
        $db = new dataBase();
        $Utils = new Utils();

        $p = $request->getQueryParams();

        $sql = sprintf("SELECT
            DISTINCT saa.idpresidio_ext,
            saa.idpresidio,
            p.presidio_out as text
            FROM ssm_associazioni_presidi saa
            LEFT JOIN ssm_import_strutture.presidi p ON p.idpresidio_ext = saa.idpresidio_ext AND p.idazienda_ext = '%s'
            WHERE saa.idazienda_ext = '%s' AND p.presidio_out IS NOT NULL AND p.presidio_out != ''", $db->real_escape_string($p['idazienda_ext']), $db->real_escape_string($p['idazienda_ext']));

        $db->query($sql);
        while ($rec = $db->fetchassoc()) {
            $ar[] = $rec;
        }

        $res['presidi'] = $ar;

        $response->getBody()->write(json_encode($res));
        return $response
    ->withStatus(200)
    ->withHeader('Content-Type', 'application/json');
    });


    $groupImport->get('/associazione/unita', function (Request $request, Response $response) use ($auth) {
        $db = new dataBase();
        $Utils = new Utils();
        $log = new Logger();

        $idazienda = $request->getQueryParams()['idazienda'];
        $idaziendaExt = $request->getQueryParams()['idazienda_ext'];
        $idpresidio = $request->getQueryParams()['idpresidio'];
        $idpresidioExt = $request->getQueryParams()['idpresidio_ext'];
        $page = $request->getQueryParams()['page'];
        $page = $page * 15;

        $sql = sprintf("SELECT SQL_CALC_FOUND_ROWS
                DISTINCT
                u.unita_out,
                u.idunita_ext,
                uo.nome as unita,
                uo.id as idunita,
                sau.idunita as idunita_assoc
                FROM
                    ssm_import_strutture.unita u
                LEFT JOIN ssm.ssm_unita_operative uo ON
                   uo.idpresidio='%s' AND
                    TRIM(LOWER(uo.nome)) like CONCAT( '%%', TRIM(LOWER(u.unita_out)), '%%' )
                    AND uo.idstatus=1
                LEFT JOIN ssm_associazioni_unita sau ON sau.idunita_ext = u.idunita_ext AND sau.idpresidio_ext = u.idpresidio_ext AND sau.idazienda_ext = u.idazienda_ext
                WHERE
                    u.idpresidio_ext = %d AND u.idazienda_ext = %d AND u.idunita_ext IS NOT NULL AND u.unita_out != ''
                ORDER BY
                    u.unita_out
                LIMIT %d, 15", $db->real_escape_string($idpresidio), $idpresidioExt, $idaziendaExt, $page);
        $db->query($sql);
        $log->log($sql);

        while ($rec = $db->fetchassoc()) {
            $rec['idazienda_ext'] = $idaziendaExt;
            $rec['idazienda'] = $idazienda;
            $rec['idpresidio_ext'] = $idpresidioExt;
            $rec['idpresidio'] = $idpresidio;
            if ($rec['idunita_assoc'] != '') {
                $rec['idunita'] = $rec['idunita_assoc'];
            }
            $ar[] = $rec;
        }
        $res['unita_all'] = $ar;

        $db->query("SELECT FOUND_ROWS()");
        $res['total'] = intval($db->fetcharray()[0]);

        $where = sprintf("idpresidio = '%s' AND idstatus=1", $idpresidio);
        $arCombo = array( "table" => "ssm.ssm_unita_operative", "value" => "id", "text" => "nome", "order" => "nome", "where" => $where );
        $res['unita'] = $Utils->_combo_list($arCombo, "", true);

        $response->getBody()->write(json_encode($res));
        return $response
      ->withStatus(200)
      ->withHeader('Content-Type', 'application/json');
    });



    $groupImport->put('/associazione/unita', function (Request $request, Response $response) use ($auth) {
        $db = new dataBase();
        $Utils = new Utils();

        $body = json_decode($request->getBody(), true);

        foreach ($body as $value) {
            unset($value['unita_out']);
            unset($value['unita']);
            unset($value['idunita_assoc']);
            $value['iduniversita'] = 1;
            $Utils->dbSql(true, "ssm_associazioni_unita", $value, "", "");
        }

        $response->getBody()->write(json_encode($res));
        return $response
          ->withStatus(200)
          ->withHeader('Content-Type', 'application/json');
    });



    $groupImport->get('/tutor/doppi_remove', function (Request $request, Response $response) use ($auth) {
        $db = new dataBase();
        $db2 = new dataBase();
        $Utils = new Utils();

        $body = json_decode($request->getBody(), true);

        // se count > 1 è doppio
        $sql = sprintf("select idstudente,idtutor,idarea,data_inizio,data_fine,count(*) as cc
          from ssm_studenti_tutor
          group by idstudente,idtutor,idarea,data_inizio,data_fine
          order by count(*) desc");
        $db->query($sql);
        while ($rec = $db->fetchassoc()) {
            $sql2 = sprintf(
                "SELECT id
            FROM ssm_studenti_tutor
            WHERE idstudente='%s'
              AND idtutor='%s'
              AND idarea='%s'
              AND data_inizio='%s'
              AND data_fine='%s'",
                $rec['idstudente'],
                $rec['idtutor'],
                $rec['idarea'],
                $rec['data_inizio'],
                $rec['data_fine']
            );
            echo "<br>CERCA DOPPIO DI " . json_encode($rec);
            $db2->query($sql2);
            $ar = array();
            while ($rec2 = $db2->fetchassoc()) {
                $ar[] = $rec2['id'];
            }

            echo "DOPPI: " . sizeof($ar);
            $c = sizeof($ar);
            if ($c > 1) {
                for ($c=1; $c<sizeof($ar); $c++) {
                    echo " cancella " . $ar[$c] . " " . $c;
                    $sql = sprintf("DELETE FROM ssm_studenti_tutor WHERE id='%s'", $ar[$c]);
                    $db2->query($sql);
                }
            }
        }

        $response->getBody()->write("FINE");
        return $response
        ->withStatus(200)
        ->withHeader('Content-Type', 'application/json');
    });



    $groupImport->get('/unicatt', function (Request $request, Response $response) use ($auth) {
        $db = new dataBase();
        $Utils = new Utils();

        echo "OK UNICATT - 2019";
        import_studenti();
//        import_tutor();
        pw_reset();


        $response->getBody()->write("FINE");
        return $response
      ->withStatus(200)
      ->withHeader('Content-Type', 'application/json');
    });


    $groupImport->get('/unimi', function (Request $request, Response $response) use ($auth) {
        $db = new dataBase();
        $Utils = new Utils();

        echo "OK UNIMI - 3";
        $ret = import_unimi_studenti();
        $ret = import_unimi_tutor();
        //pw_unimi_reset();


        $hashedPassword = password_hash("2.daCs£.a3!", PASSWORD_DEFAULT);
        $sql = sprintf("UPDATE ssm.ssm_utenti SET password='%s' WHERE password='NOAUTH_UNIMI'", $db->real_escape_string($hashedPassword));
        $db->query($sql);
        echo "<br>" . $sql;
        echo "<br>" . "RESET PASSWORD OK";


        /*
        import_unimi_studenti();
        import_unimi_tutor();
        pw_unimi_reset();
        */

        $response->getBody()->write("FINE");
        return $response
        ->withStatus(200)
        ->withHeader('Content-Type', 'application/json');
    });
});


  function getReteTutor($codiceFiscale)
  {
      $db = new Database();

      $sql = sprintf("SELECT
            DISTINCT rt.CF_TUTOR,
            au.idazienda,
            au.idpresidio,
            au.idunita
        FROM
            ssm_unimi_import.ROTAZIONE_TUTOR rt
            LEFT JOIN ssm_unimi_import.ANAGRAFICA_RUOLO ar ON ar.COD_FISCALE = rt.CF_TUTOR AND ar.ID_RUOLO = 5 AND ar.COD_SCUOLA = '000'
            LEFT JOIN ssm_unimi_import.ANAGRAFICA an ON an.COD_FISCALE = rt.CF_TUTOR
            LEFT JOIN ssm_unimi_import.ANAGRAFICA_RETE arete ON arete.COD_FISCALE = rt.CF_TUTOR
            LEFT JOIN ssm_unimi_import.RETE arf ON arf.ID_RETE = arete.ID_RETE
            LEFT JOIN ssm_associazioni_unita au ON au.idazienda_ext = arf.COD_AZIENDA
                AND au.idpresidio_ext = arf.COD_PRESIDIO
                AND au.idunita_ext = arf.COD_UO
        WHERE
            ID_RUOLO = 5
            AND rt.COD_SCUOLA = '000'
            AND rt.ATTIVO =1
            AND rt.CF_TUTOR = '%s'", $db->real_escape_string($codiceFiscale));
      $db->query($sql);
      while ($rec = $db->fetchassoc()) {
          $ar[] = $rec;
      }
      return $ar;
  }


  function coorte_duplica($idcoorte_from)
  {
      $db = new dataBase();
      $uuid = new UUID();
      $Utils = new Utils();
      $log = new Logger();

      $sql = sprintf("SELECT *
      FROM ssm.ssm_pds_coorti
      WHERE id='%s'", $db->real_escape_string($idcoorte_from));
      $db->query($sql);
      $rec = $db->fetchassoc();

      // Imposta il nuovo nome
      $idcoorte_new = $uuid->v4();

      $log->log("Duplico la coorte $idcoorte_from, nuovo id: $idcoorte_new");

      $rec['id'] = $idcoorte_new;
      $rec['nome'] = $rec['nome'] . " - copia";
      $rec['date_create'] = "now()";
      $rec['date_update'] = "now()";
      $rec['idstatus'] = 1;
      $retCoorte = $Utils->dbSql(true, "ssm.ssm_pds_coorti", $rec, "", "");
      if ($retCoorte['success'] != 1) {
          $log->log("Errore duplicazione coorte: " . $idcoorte_from . " " . json_encode($retCoorte));
          $ret = array(
        "success" => 0,
        "error" => "Errore duplicazione coorte"
      );
          return $ret;
      }


      //echo "\r\nInserimento ssm.ssm_pds_coorti_contatori:\r\n";
      $sql = sprintf("SELECT * FROM ssm.ssm_pds_coorti_contatori WHERE idcoorte='%s' AND idstatus=1", $db->real_escape_string($idcoorte_from));
      //echo "\r\nssm.ssm_pds_coorti_contatori:\r\n";
      //echo $sql;
      $db->query($sql);
      while ($recContatore = $db->fetchassoc()) {
          $recContatore['id'] = $uuid->v4();
          $recContatore['idcoorte'] = $idcoorte_new;
          $recContatore['date_create'] = "now()";
          $recContatore['date_update'] = "now()";
          $recContatore['idstatus'] = 1;
          $retContatore = $Utils->dbSql(true, "ssm.ssm_pds_coorti_contatori", $recContatore, "", "");
          if ($retContatore['success'] != 1) {
              $log->log("Errore duplicazione ssm.ssm_pds_coorti_contatori: " . $idcoorte_from . " " . json_encode($retCoorte));
              $ret = array(
          "success" => 0,
          "error" => "Errore duplicazione ssm.ssm_pds_coorti_contatori"
        );
              return $ret;
          }
      }


      $idTipologiaNew = array();
      $sql = sprintf("SELECT * FROM ssm.ssm_registrazioni_attivita_tipologie WHERE idcoorte='%s' AND idstatus=1", $db->real_escape_string($idcoorte_from));
      $db->query($sql);
      while ($recAttTipologie = $db->fetchassoc()) {
          $newId = $uuid->v4();
          $idTipologiaNew[$recAttTipologie['id']] = $newId;
          $recAttTipologie['id'] = $newId;
          $recAttTipologie['idcoorte'] = $idcoorte_new;
          $recAttTipologie['date_create'] = "now()";
          $recAttTipologie['date_update'] = "now()";
          $recAttTipologie['idstatus'] = 1;
          $retAttTipologie = $Utils->dbSql(true, "ssm.ssm_registrazioni_attivita_tipologie", $recAttTipologie, "", "");
          if ($retAttTipologie['success'] != 1) {
              $log->log("Errore duplicazione ssm.ssm_registrazioni_attivita_tipologie: " . $idcoorte_from . " " . json_encode($retAttTipologie));
              $ret = array(
          "success" => 0,
          "error" => "Errore duplicazione ssm.ssm_registrazioni_attivita_tipologie"
        );
              return $ret;
          }
      }

      $idAttivitaNew = array();
      //echo "\r\nInserimento ssm.ssm_registrazioni_attivita:\r\n";
      $sql = sprintf("SELECT * FROM ssm.ssm_registrazioni_attivita WHERE idcoorte='%s' AND idstatus=1", $db->real_escape_string($idcoorte_from));
      //echo "\r\nregistrazioni_attivita:\r\n";
      $db->query($sql);
      while ($recAttivita = $db->fetchassoc()) {
          $oldId = $recAttivita['id'];
          $newId = $uuid->v4();
          $idAttivitaNew[$recAttivita['id']] = $newId;
          $recAttivita['id'] = $newId;
          $recAttivita['idtipo_attivita'] = $idTipologiaNew[$recAttivita['idtipo_attivita']];
          $recAttivita['idcoorte'] = $idcoorte_new;
          $recAttivita['date_create'] = "now()";
          $recAttivita['date_update'] = "now()";
          $recAttivita['idstatus'] = 1;
          $retAttivita = $Utils->dbSql(true, "ssm.ssm_registrazioni_attivita", $recAttivita, "", "");
          if ($retAttivita['success'] != 1) {
              $log->log("Errore duplicazione ssm.ssm_registrazioni_attivita: " . $idcoorte_from . " " . json_encode($retAttivita));
              $ret = array(
          "success" => 0,
          "error" => "Errore duplicazione ssm.ssm_registrazioni_attivita"
        );
              return $ret;
          } else {
              $filters = copyFilters($oldId, $newId);
              if ($filters['success'] != 1) {
                  return $filters;
              }
              $schema = copySchema($oldId, $newId);
              if ($schema['success'] != 1) {
                  return $schema;
              }
          }
      }

      $sql = sprintf("SELECT * FROM ssm.ssm_scuole_registrazioni_np_calendario WHERE idcoorte='%s' AND idstatus=1", $db->real_escape_string($idcoorte_from));
      //echo "\r\nssm.ssm_scuole_registrazioni_np_calendario:\r\n";
      $db->query($sql);
      while ($recCalendario = $db->fetchassoc()) {
          $recCalendario['id'] = $uuid->v4();
          $recCalendario['idcoorte'] = $idcoorte_new;
          $recCalendario['date_create'] = "now()";
          $recCalendario['date_update'] = "now()";
          $recCalendario['idstatus'] = 1;
          $retCalendario = $Utils->dbSql(true, "ssm.ssm_scuole_registrazioni_np_calendario", $recCalendario, "", "");
          if ($retAttivita['success'] != 1) {
              $log->log("Errore duplicazione ssm.ssm_scuole_registrazioni_np_calendario: " . $idcoorte_from . " " . json_encode($retCalendario));
              $ret = array(
          "success" => 0,
          "error" => "Errore duplicazione ssm.ssm_scuole_registrazioni_np_calendario"
        );
              return $ret;
          }
      }



      $sql = sprintf("SELECT * FROM ssm.ssm_pds_coorti_export WHERE idcoorte='%s' AND idstatus=1", $db->real_escape_string($idcoorte_from));
      //echo "\r\nssm.ssm_scuole_registrazioni_np_calendario:\r\n";
      $db->query($sql);
      while ($recExport = $db->fetchassoc()) {
          $recExport['id'] = $uuid->v4();
          $recExport['idcoorte'] = $idcoorte_new;
          $recExport['date_create'] = "now()";
          $recExport['date_update'] = "now()";
          $recExport['idstatus'] = 1;
          $recExport['struttura'] = renewIdAttivita($idAttivitaNew, $recExport['struttura']);
          $retExport = $Utils->dbSql(true, "ssm.ssm_pds_coorti_export", $recExport, "", "");
          if ($retExport['success'] != 1) {
              $log->log("Errore duplicazione ssm.ssm_pds_coorti_export: " . $idcoorte_from . " " . json_encode($retExport));
              $ret = array(
          "success" => 0,
          "error" => "Errore duplicazione ssm.ssm_pds_coorti_export"
        );
              return $ret;
          }
      }




      $sql = sprintf("SELECT * FROM ssm.ssm_pds WHERE idcoorte='%s' AND idstatus=1", $db->real_escape_string($idcoorte_from));
      $db->query($sql);
      while ($recPds = $db->fetchassoc()) {
          $recPds['id'] = $uuid->v4();
          $recPds['idcoorte'] = $idcoorte_new;
          $recPds['date_create'] = "now()";
          $recPds['date_update'] = "now()";
          $recPds['idstatus'] = 1;
          $retPds = $Utils->dbSql(true, "ssm.ssm_pds", $recPds, "", "");
          if ($retPds['success'] != 1) {
              $log->log("Errore duplicazione ssm.ssm_pds: " . $idcoorte_from . " " . json_encode($retPds));
              $ret = array(
          "success" => 0,
          "error" => "Errore duplicazione ssm.ssm_pds"
        );
              return $ret;
          }
      }


      $ret['success'] = 1;
      $ret['error'] = "";
      return $ret;
  }

  function copyFilters($idAttivita, $idAttivitaNew)
  {
      $log = new Logger();
      $db = new dataBase();
      $uuid = new UUID();
      $Utils = new Utils();

      $sql = sprintf("SELECT * FROM ssm.ssm_registrazioni_filtri WHERE idattivita='%s' AND idstatus=1", $db->real_escape_string($idAttivita));
      $db->query($sql);
      while ($recFilter = $db->fetchassoc()) {
          $recFilter['id'] = $uuid->v4();
          $recFilter['idattivita'] = $idAttivitaNew;
          $recFilter['date_create'] = "now()";
          $recFilter['date_update'] = "now()";
          $retFilter = $Utils->dbSql(true, "ssm.ssm_registrazioni_filtri", $recFilter, "", "");
          if ($retFilter['success'] != 1) {
              $log->log("Errore duplicazione ssm.ssm_registrazioni_filtri: " . $idAttivita . " " . json_encode($retFilter));
              $ret = array(
          "success" => 0,
          "error" => "Errore duplicazione ssm.ssm_registrazioni_filtri"
        );
              return $ret;
          }
      }
      return array("success" => 1);
  }


  function copySchema($idAttivita, $idAttivitaNew)
  {
      $log = new Logger();
      $db = new dataBase();
      $uuid = new UUID();
      $Utils = new Utils();

      $sql = sprintf("SELECT * FROM ssm.ssm_registrazioni_schema WHERE idattivita='%s' AND idstatus=1", $db->real_escape_string($idAttivita));
      $db->query($sql);
      while ($recSchema = $db->fetchassoc()) {
          $recSchema['id'] = $uuid->v4();
          $recSchema['idattivita'] = $idAttivitaNew;
          $recSchema['date_create'] = "now()";
          $recSchema['date_update'] = "now()";
          $retSchema = $Utils->dbSql(true, "ssm.ssm_registrazioni_schema", $recSchema, "", "");
          if ($retSchema['success'] != 1) {
              $log->log("Errore duplicazione ssm.ssm_registrazioni_schema: " . $idAttivita . " " . json_encode($retSchema));
              $ret = array(
          "success" => 0,
          "error" => "Errore duplicazione ssm.ssm_registrazioni_schema"
        );
              return $ret;
          }
      }
      return array("success" => 1);
  }


  function renewIdAttivita($idAttivitaNew, $struttura)
  {
      $struttura = json_decode($struttura, true);
      foreach ($struttura as $key => $value) {
          foreach ($value as $k => $v) {
              if ($v['id'] == 'idattivita') {
                  foreach ($v['idvalue'] as $kval => $val) {
                      $struttura[$key][$k]['idvalue'][$kval] = $idAttivitaNew[$val];
                  }
                  foreach ($v['options'] as $kopt => $opt) {
                      $struttura[$key][$k]['options'][$kopt]['id'] = $idAttivitaNew[$opt['id']];
                  }
              }
          }
      }
      return json_encode($struttura);
  }

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




$app->group('/specializzando_registrazioni', function (RouteCollectorProxy $groupSpecRegistrazioni) use ($auth) {

  $data = [
    "log" => true,
    "table" => "ssm_registrazioni",
    "id" => "id",
    "sort" => "nome",
    "order" => "asc",
    "status_field" => "idstatus",
    "update_field" => "date_update",
    "create_field" => "date_create"
  ];

  $crud = new CRUD( $data );

  // list
  $groupSpecRegistrazioni->get('', function (Request $request, Response $response, $args) use ($auth, $crud) {
    $log = new Logger();
    $Utils = new Utils();

    $user = $request->getAttribute('user');
    if ($user['idruolo'] == 8) {
      $specializzando = specializzando_get($user['id']);
    }

    $log->log( "utente: " . json_encode( $user ) );

    $p = $request->getQueryParams();

    $log->log( json_encode($p) );
    $data = [
      "log" => true,
      "table" => "ssm_registrazioni",
      "id" => "ssm_registrazioni.id",
      "sort" => "ssm_registrazioni.data_registrazione",
      "order" => "asc",
      "status_field" => "idstatus",
      "update_field" => "date_update",
      "create_field" => "date_create",
      "list_fields" => [ "ssm_registrazioni.id",
        "date_format(data_registrazione,'%d-%m-%Y') as data_registrazione_text",
        "data_registrazione",
        "attach",
        "quantita", "struttura_text",
        "ra.nome as attivita_text", "ci.nome as prestazione_text",
        "ssm_registrazioni.struttura as combo",
        "CONCAT(su.nome, ' ', su.cognome) as tutor_nome",
        "conferma_stato",
        "case conferma_stato
          when 0 then 'Da inviare'
          when 1 then 'Inviato'
          when 2 then 'Confermato'
          when 3 then 'Scartato'
        end as conferma_stato_text",
        "case conferma_stato
          when 1 then 'Da confermare'
          when 2 then 'Confermato'
          when 3 then 'Scartato'
          end as conferma_stato_tutor_text",
        "case conferma_stato
          when 0 then '#cc0000'
          when 1 then '#032b6b'
          when 2 then '#258e7a'
          when 3 then '#6e6e6e'
          end as button_color"
      ],
      "list_join" => [
        [
            "ssm.ssm_registrazioni_attivita ra",
            " ra.id=ssm_registrazioni.idattivita "
        ],
        [
            "ssm.ssm_registrazioni_combo_items ci",
            " ci.id=ssm_registrazioni.idprestazione"
        ],
        [
            "ssm_turni st",
            "st.idspecializzando=ssm_registrazioni.idutente
            AND st.data_inizio <= ssm_registrazioni.data_registrazione
            AND st.data_fine >= ssm_registrazioni.data_registrazione
            AND st.idstatus=1
            AND st.idscuola = '" . $user['idscuola'] . "'
            AND st.anno = " . $specializzando['anno_scuola']
        ],
        [
            "ssm_utenti su",
            "su.id=st.idtutor"
        ]
      ]
    ];

    if ($p['srt'] == "data_registrazione_text") {
      $p['srt'] = "data_registrazione";
    }

    $p['_ssm_registrazioni.idstatus'] = "1";
    $p['>ssm_registrazioni.quantita'] = "0";

    if( $p['s'] != "" ) {
      $p['search'] = array(
        "field" => "ci.nome",
        "operator" => " LIKE ",
        "value" => "%" . $p['s'] . "%"
      );
    }

    if ($p['idprestazione'] != '') {
      $p['_ssm_registrazioni.idprestazione'] = $p['idprestazione'];
    }
    if ($p['idattivita'] != '') {
        $p['_ssm_registrazioni.idattivita'] = $p['idattivita'];
    }
    if ($p['idtutor'] != '') {
        $p['_ssm_registrazioni.idtutor'] = $p['idtutor'];
    }
    if ($p['conferma_stato'] != '') {
        $p['_ssm_registrazioni.conferma_stato'] = $p['conferma_stato'];
    }
    if ($p['data_registrazione'] != '') {
        $p['_ssm_registrazioni.data_registrazione'] = $p['data_registrazione'];
    }
    if ($p['idcoorte'] != '') {
        $p['_ssm_registrazioni.idcoorte'] = $p['idcoorte'];
    }

    if( $user['idruolo'] == 7 ) {
      $res = registrazioni_tutor_get($user['id'], $p, $p['idspecializzando'], $p['trainerTutor']);

      // Prendo le attività le prestazioni e lo stato per i filtri
      $res['attivita_list'] = getAttivitaDistinct($p['idspecializzando']);
      $res['prestazioni_list'] = getPrestazioniDistinct($p['idspecializzando']);
      $res['status_list'] = getStatusDistinct($p['idspecializzando']);
      $res['tutor_list'] = getTutorDistinct($p['idspecializzando']);
    } else if( $user['idruolo'] == 8 ) {
    // } else if( $user['idruolo'] == 5 || $user['idruolo'] == 9 ) {
      $p['_ssm_registrazioni.idutente'] = $user['id'];
      $p['_ssm_registrazioni.idscuola_specializzazione'] = $user['idscuola'];
      $crudRegistrazioni = new CRUD($data);

      $res = $crudRegistrazioni->record_list($p);

      // Prendo le attività le prestazioni e lo stato per i filtri
      $res['attivita_list'] = getAttivitaDistinct($user['id']);
      $res['prestazioni_list'] = getPrestazioniDistinct($user['id']);
      $res['status_list'] = getStatusDistinct($user['id']);
      $res['tutor_list'] = getTutorDistinct($user['id']);
      $res['ore_valutate'] = getOreValutate($user['id']);
    } else {
      $res = registrazioni_dir_get($p['idspecializzando'], $user['idscuola'], $p);

      // Prendo le attività le prestazioni e lo stato per i filtri
      $res['attivita_list'] = getAttivitaDistinct($p['idspecializzando']);
      $res['prestazioni_list'] = getPrestazioniDistinct($p['idspecializzando']);
      $res['status_list'] = getStatusDistinct($p['idspecializzando']);
      $res['tutor_list'] = getTutorDistinct($p['idspecializzando']);
      $res['coorti_list'] = getCoorti($user['idscuola']);
      $res['anni_list'] = getAnni($user['idscuola']);
    }
    if( !$res )
      $res = [];

    $response->getBody()->write( json_encode( $res ) );
    return $response;
  });

  function getOreValutate($idUtente) {
    $db = new dataBase();
    $log = new Logger();
    $sql = sprintf("SELECT SUM(quantita) as ore
      FROM ssm_registrazioni
      WHERE idutente='%s'
      AND idstatus=1 AND conferma_stato = 2", $db->real_escape_string( $idUtente ) );
    $db->query($sql);
    $log->log($sql);
    $row = $db->fetchassoc();
    return $row['ore'];
  }

  function getCoorti($idScuola) {
    $db = new dataBase();
    $log = new Logger();
    $sql = sprintf("SELECT id, nome as text
      FROM ssm.ssm_pds_coorti pc
      WHERE idscuola_specializzazione='%s'
      AND idstatus=1", $db->real_escape_string( $idScuola ) );
    $db->query($sql);
    $log->log($sql);
    while ($row = $db->fetchassoc()) {
        $ar[] = $row;
    }
    return $ar;
  }

  function getAnni($idScuola) {
    $db = new dataBase();
    $sql = sprintf("SELECT numero_anni
      FROM ssm.ssm_scuole_atenei sa
      LEFT JOIN ssm.ssm_scuole s ON s.id=sa.idscuola
      WHERE sa.id='%s'
      AND sa.idstatus=1", $db->real_escape_string( $idScuola ) );
    $db->query($sql);
    $row = $db->fetchassoc();
    $n = 1;
    while($n <= $row['numero_anni'])  {
      $ar[] = array("id" => $n, "text" => $n);
      $n++;
    }
    return $ar;
  }

  function getAttivitaDistinct( $idSpecializzando ) {
    $db = new dataBase();
    $sql = sprintf("SELECT DISTINCT idattivita as id, sra.nome as text
      FROM ssm_registrazioni sr
      LEFT JOIN ssm.ssm_registrazioni_attivita sra ON sra.id=sr.idattivita
      WHERE sr.idutente='%s'
      AND sr.idstatus=1", $db->real_escape_string( $idSpecializzando ) );
    $db->query($sql);
    while($row = $db->fetchassoc()) {
      $ar[] = $row;
    }
    return $ar;
  }

  function getPrestazioniDistinct( $idSpecializzando ) {
    $db = new dataBase();
    $log = new Logger();
    $sql = sprintf("SELECT DISTINCT idprestazione as id, rci.nome as text
      FROM ssm_registrazioni sr
      LEFT JOIN ssm.ssm_registrazioni_combo_items rci ON rci.id=sr.idprestazione
      WHERE sr.idutente='%s'
      AND sr.idstatus=1", $db->real_escape_string( $idSpecializzando ) );
    $log->log("SQL: " . $sql);
    $db->query($sql);
    while($row = $db->fetchassoc()) {
      $ar[] = $row;
    }
    return $ar;
  }

  function getStatusDistinct($idSpecializzando) {
    $db = new dataBase();
    $log = new Logger();
    $sql = sprintf("SELECT DISTINCT conferma_stato as id, rs.stato as text
      FROM ssm_registrazioni sr
      LEFT JOIN ssm.ssm_registrazioni_stato rs ON rs.id=sr.conferma_stato
      WHERE sr.idutente='%s'
      AND sr.idstatus=1", $db->real_escape_string( $idSpecializzando ) );
    $log->log("SQL: " . $sql);
    $db->query($sql);
    while ($row = $db->fetchassoc()) {
        $ar[] = $row;
    }
    return $ar;
  }

  function getTutorDistinct($idSpecializzando) {
    $db = new dataBase();
    $log = new Logger();
    $sql = sprintf("SELECT DISTINCT idtutor as id, CONCAT(su.nome, ' ', su.cognome) as text
      FROM ssm_registrazioni sr
      LEFT JOIN ssm_utenti su ON su.id=sr.idtutor
      WHERE sr.idutente='%s'
      AND sr.idstatus=1", $db->real_escape_string( $idSpecializzando ) );
    $log->log("SQL: " . $sql);
    $db->query($sql);
    while ($row = $db->fetchassoc()) {
        $ar[] = $row;
    }
    return $ar;
  }


  // get
  $groupSpecRegistrazioni->get('/{id}', function (Request $request, Response $response, $args) use ($crud, $authMW) {
      $Utils = new Utils();
      $log = new Logger();
      $db = new dataBase();

      $p = $request->getQueryParams();
      $user = $request->getAttribute('user');

      $specializzando = specializzando_get($p['idspecializzando'] ? $p['idspecializzando'] : $user['id']);
      $idcoorte = $specializzando['idcoorte'];
      $log->log( "user: " . json_encode( $user ) );

      $data = getAttivita($user['idruolo'], $args['id']);

      if( $data == "" ) {
        $data = [];
        $res = [];
      } else {
        $res['data'] = $data;

        $log->log( "GET /attivita" . json_encode( $res ) );

        $combo = json_decode( $res['combo'], true );
        foreach( $combo as $k => $v ) {
          $res[$v['idcombo']] = $v['idvalue'];
        }

        $combo = json_decode( $res['combo_implicite'], true );
        foreach( $combo as $k => $v ) {
          $res[$v['idcombo']] = $v['idvalue'];
        }

        unset( $res['combo'] );

        $res['data']['attach'] = ($res['data']['attach'] == "" ? [] : json_decode($res['data']['attach'], true));


        /*
        if (sizeof($res['data']['attach']) > 0) {
            $arAttach = [];
            $sql = sprintf("SELECT id, original_file_name as attach_name
            FROM attach WHERE id IN ('%s')", implode("','", $res['data']['attach'] ));
            $log->log("attach_list: " . $sql);
            $db->query($sql);
            while ($rec = $db->fetchassoc()) {
                $arAttach[] = $rec;
            }
            $res['data']['attach'] = $arAttach;
        }
        */
    }

    if (!isset($p['idspecializzando'])) {
        // Verifica la presenza di turni dello specializzando
        $turni = turni_specializzando_list($specializzando['id'], $specializzando['idscuola'], $specializzando['anno_scuola']);
        $log->log("turni specializzando: " . json_encode($turni));

        // se non ha turni
        if ($turni) {
            $log->log("Specializzando ha turni " . json_encode($turni));
            foreach ($turni as $k => $v) {
              $v['idunita'] = $db->real_escape_string( $v['idunita'] );
              $arUnita[] = $v['idunita'];
            }

            $log->log("UNITA: " . json_encode($arUnita));

            $whereUnita = sprintf("AND su.id IN ('%s')", implode("','", $arUnita ));
        }

        // TODO: SQL_INJECTION_TEST
        // seleziona le unità operative
        $sql = sprintf("SELECT idunita as id, concat(uo.nome, ' - ', pr.nome) as text
          FROM ssm.ssm_scuole_unita su
          LEFT JOIN ssm.ssm_unita_operative uo ON uo.id=su.idunita
          LEFT JOIN ssm.ssm_presidi pr ON pr.id=uo.idpresidio
            WHERE idscuola_specializzazione='%s'
            AND su.idstatus=1 AND uo.idstatus=1 AND pr.idstatus=1
            %s
          ORDER by uo.nome", $db->real_escape_string( $user['idscuola'] ), $whereUnita);
        $log->log("UNITA' SCUOLA: " . $sql);
        $db->query($sql);
        while ($rec = $db->fetchassoc()) {
            $ar[] = $rec;
        }
        $res['unita_list'] = $ar;
    } else {
      $res['unita_list'] = [];
    }

    if ($res['data']['idtutor']) {
      $retUnita = unita_info($res['data']['idunita']);
      $res['tutor_list'] = $retUnita['tutor_list'];
      $res['data']['direttore'] = $retUnita['direttore'];

      $res['attivita_list'] = attivita_list($specializzando['idscuola'], $idcoorte);
    }
    if ($res['data']['idattivita']) {
      $res['prestazioni_list'] = prestazioni_list($res['data']['idattivita']);
    }
    if ($res['data']['idprestazione']) {
      // print_r(getComboAttivita($res['data']['idattivita'], $res['data']['idprestazione'], $specializzando['idscuola'], $idcoorte));
      $res['data'] = array_merge($res['data'], getComboAttivita($res['data']['idattivita'], $res['data']['idprestazione'], $specializzando['idscuola'], $idcoorte));
    }
    if ($res['data']['idattivita'] && $res['data']['idprestazione'] && $res['data']['struttura'])  {
      $res['autonomiaList'] = autonomiaGet($res['data']['idutente'], json_decode($res['data']['struttura'], true), $res['data']['idscuola_specializzazione'], $res['data']['idcoorte'], $res['data']['idprestazione']);
      $res['data']['idautonomia'] = (int)$res['data']['autonomia'];
    }

    // Prende i campi aggiuntivi per l'attività

    $res['additional_fields'] = getAttivitaFields($res['data']['idattivita']);

      $response->getBody()->write( json_encode( $res ) );
      return $response;
  });

  function getAttivita($idRuolo, $idAttivita)  {
    if ($idRuolo == 8)  {
      $data = [
        "log" => true,
        "table" => "ssm_registrazioni",
        "id" => "id",
        "sort" => "nome",
        "order" => "asc",
        "status_field" => "idstatus",
        "update_field" => "date_update",
        "create_field" => "date_create"
      ];

      $crud = new CRUD($data);
      return $crud->record_get($idAttivita)['data'][0];
    } else {
      $Utils = new Utils();
      $res = $Utils->dbSelect([
        "log" => true,
        "select" => [
          "r.*",
          "CONCAT(uo.nome, ' ', pr.nome) as nome_unita",
          "CONCAT(u.nome, ' ', u.cognome) as nome_tutor",
          "ra.nome as nome_attivita",
          "ra.idtipo_registrazione"
        ],
        "from" => "ssm_registrazioni r",
        "join" => [
          [
              "ssm.ssm_unita_operative uo",
              " uo.id=r.idunita "
          ],
          [
            "ssm.ssm_presidi pr",
            "pr.id=uo.idpresidio"
          ],
          [
            "ssm.ssm_registrazioni_attivita ra",
            "ra.id=r.idattivita"
          ],
          [
            "ssm_utenti u",
            "u.id=r.idtutor"
          ]
        ],
        "map" => function ($rec) {
          $rec['autonomia'] = autonomia_text( $rec['autonomia'] );
          return $rec;
        },
        "where" => [
          [
          "field" => "r.id",
          "value" => $idAttivita,
          ]
        ],
      ]);
      return $res['data'][0];
    }
  }

  // new
  $groupSpecRegistrazioni->put('', function (Request $request, Response $response, $args) use ($crud ) {
    $log = new Logger();
    $Utils = new Utils();
    $uuid = new UUID();

    $p = json_decode($request->getBody(), true);
    $log->log( "PUT /specializzando_registrazione - " . json_encode( $p ) );

    $user = $request->getAttribute('user');
    $specializzando = specializzando_get($user['id']);
    $idcoorte = $specializzando['idcoorte'];

    // TODO: Cancellabile
    // $strutturaFull = comboImpliciteCalc($p['struttura'], $p['idattivita'], $p['idprestazione']);
    // $add['struttura_full'] = json_encode($strutturaFull);

    if ($p['struttura'] !== true)  {
      $strutturaFull = comboImpliciteCalc($p['struttura'], $p['idattivita'], $p['idprestazione']);
      $add['struttura_full'] = json_encode($strutturaFull);
    } else {
      $p['struttura'] = [];
      $strutturaFull = comboImpliciteCalc([], $p['idattivita'], $p['idprestazione']);
      $add['struttura_full'] = json_encode($strutturaFull);
    }

    $s = "";
    foreach( $p['struttura'] as $k => $v ) {
      $s .= " " . $v['nome'] . " -> ";
      foreach( $v['options'] as $a => $b ) {
        if( array_search( $b['id'], $v['idvalue'] ) !== false )
          $s .= " " . $b['ci_nome'];
      }
    }
    $add['struttura_text'] = $s;

    // $log->log("Contatori");
    // $log->log(json_encode($p['struttura']));
    // $log->log(json_encode($strutturaFull));

    // $contatori = searchContatori($p['struttura'], $user['idscuola'], $specializzando['idcoorte'], $p['idprestazione']);
    $contatori = searchContatori($strutturaFull, $user['idscuola'], $specializzando['idcoorte'], $p['idprestazione'], $user['id']);

    // $log->log(json_encode($contatori));
    // $log->log(json_encode($contatori2));
    // $log->log("Fine contatori");


    $idContatori = array();
    foreach ($contatori as $key => $value) {
      $idContatori[] = $value['id'];
    }

    foreach( $p['selectedDays'] as $k => $v ) {

      $add['id'] = $uuid->v4();
      $add['idscuola_specializzazione'] = $user['idscuola'];
      $add['idunita'] = $p['idunita'];
      $add['idtutor'] = $p['idtutor'];
      $add['idutente'] = $user['id'];
      $add['idcoorte'] = $idcoorte;
      $add['struttura'] = json_encode( $p['struttura'] );

      $add['contatori'] = json_encode($idContatori);

      $add['data_registrazione'] = substr( $v['text_date'], 0, 10 );
      $add['anno'] = $specializzando['anno_scuola'];
      $add['quantita'] = $v['badgeTotal'];
      $add['idprestazione'] = $p['idprestazione'];
      $add['idattivita'] = $p['idattivita'];
      $add['autonomia'] = $p['idautonomia'];

      $add['attach'] = $p['attach'] != "" ? json_encode( $p['attach'] ) : "[]";
      $add['note'] = $p['note'];
      $add['protocollo'] = $p['protocollo'];

      $add['conferma_stato'] = 0;
      $add['conferma_data'] = "now()";

      $add['idstatus'] = 1;
      $add['date_create'] = "now()";
      $add['date_update'] = "now()";

      $res = $Utils->dbSql( true, "ssm_registrazioni", $add, "", "" );
      $res['success'] = 1;

    }


    if( $res['success'] == 1 ) {
      $response->getBody()->write( json_encode( $res ) );
        return $response
          ->withStatus(200)
          ->withHeader('Content-Type', 'application/json');
    } else {
        $response->getBody()->write( "Errore aggiornamento" );
        return $response
          ->withStatus(400)
          ->withHeader('Content-Type', 'text/plain');
    }

    return $response;
  });


  //update
  $groupSpecRegistrazioni->post('/{id}', function (Request $request, Response $response, $args) use ($crud) {
    $log = new Logger();

    $p = json_decode($request->getBody(), true);
    $log->log( "update - " . json_encode( $p ) );

    // Se struttura è true vuol dire che non ci sono combo
    // TODO: Cancellabile
    // if ($p['struttura'] != true)  {
    //   $add['struttura_full'] = json_encode(comboImpliciteCalc($p['struttura'], $p['idattivita'], $p['idprestazione']));
    // } else {
    //   $p['struttura'] = [];
    //   $add['struttura_full'] = json_encode(comboImpliciteCalc([], $p['idattivita'], $p['idprestazione']));
    // }
    if ($p['struttura'] !== true)  {
      $strutturaFull = comboImpliciteCalc($p['struttura'], $p['idattivita'], $p['idprestazione']);
      $add['struttura_full'] = json_encode($strutturaFull);
    } else {
      $p['struttura'] = [];
      $strutturaFull = json_encode(comboImpliciteCalc([], $p['idattivita'], $p['idprestazione']));
      $add['struttura_full'] = json_encode($strutturaFull);
    }

    $s = "";
    foreach( $p['struttura'] as $k => $v ) {
      $s .= " " . $v['nome'] . " -> ";
      foreach( $v['options'] as $a => $b ) {
        if( array_search( $b['id'], $v['idvalue'] ) !== false )
          $s .= " " . $b['ci_nome'];
      }
    }
    $add['struttura_text'] = $s;

    $add['struttura'] = json_encode( $p['struttura'] );
    $add['data_registrazione'] = substr( $p['data_registrazione'], 0, 10 );
    $add['quantita'] = $p['quantita'];
    $add['idprestazione'] = $p['idprestazione'];
    $add['idattivita'] = $p['idattivita'];
    $add['autonomia'] = $p['idautonomia'];
    $add['attach'] = $p['attach'] != "" ? json_encode($p['attach']) : "[]";
    $add['note'] = $p['note'];
    $add['protocollo'] = $p['protocollo'];
    $add['date_update'] = "now()";


    $retValidate = validate( "registrazione", $ar );
    if( $retValidate != "" ) {
      $response->getBody()->write( $retValidate );
      return $response
        ->withStatus(400)
        ->withHeader('Content-Type', 'text/plain');
    }

    $res = $crud->record_update( $args['id'], $add );
    if( $res['success'] == 1 ) {
      $response->getBody()->write( json_encode( $res ) );
        return $response
          ->withStatus(200)
          ->withHeader('Content-Type', 'application/json');
    } else {
        $response->getBody()->write( "Errore aggiornamento" );
        return $response
          ->withStatus(400)
          ->withHeader('Content-Type', 'text/plain');
    }
  });

  // delete
  $groupSpecRegistrazioni->delete('/{id}', function (Request $request, Response $response, $args) use ($crud) {
    $res = $crud->record_delete( $args['id'] );
    if( $res['success'] == 1 ) {
      $response->getBody()->write( json_encode( $res ) );
        return $response
          ->withStatus(200)
          ->withHeader('Content-Type', 'application/json');
    } else {
        $response->getBody()->write( "Errore aggiornamento" );
        return $response
          ->withStatus(400)
          ->withHeader('Content-Type', 'text/plain');
    }
  });



  // lista dei tutor dell'unita operativa
  $groupSpecRegistrazioni->get('/unita_info/{idunita}', function (Request $request, Response $response, $args) use ($crud, $authMW) {
    $res = unita_info( $args['idunita'] );
    $response->getBody()->write( json_encode( $res ) );
    return $response;
  });


  // lista delle attività relative alla scuola e alla coorte dello specializzando
  $groupSpecRegistrazioni->get('/attivita/list', function (Request $request, Response $response, $args) use ($crud, $authMW) {
    $db = new dataBase();
    $log = new Logger();

    $user = $request->getAttribute('user');
    $specializzando = specializzando_get( $user['id'] );
    $idcoorte = $specializzando['idcoorte'];
    $idscuola = $user['idscuola'];

    $sql = sprintf( "SELECT id, nome as text, opzione_note, opzione_protocollo, opzione_upload, idtipo_registrazione
      FROM ssm.ssm_registrazioni_attivita
      WHERE idscuola='%s'
        AND idcoorte='%s'
        AND idstatus=1
      ORDER BY nome",
      $db->real_escape_string( $idscuola ), $db->real_escape_string( $idcoorte ) );
    $log->log($sql);
    $db->query( $sql );
    while( $rec = $db->fetchassoc() ) {
      $ar[] = $rec;
    }

    $res['attivita_list'] = $ar;
    if (!$res['attivita_list'])
      $res['attivita_list'] = [];

    $response->getBody()->write( json_encode( $res ) );
    return $response;
  });



  // invia prestazioni
  $groupSpecRegistrazioni->get('/attivita/list/{idattivita}', function (Request $request, Response $response, $args) use ($crud, $authMW) {
    $db = new dataBase();
    $log = new Logger();

    $user = $request->getAttribute('user');
    $specializzando = specializzando_get( $user['id'] );
    $idcoorte = $specializzando['idcoorte'];
    $idscuola = $user['idscuola'];

    // $sql = sprintf( "SELECT idtipo_registrazione
    //   FROM ssm.ssm_registrazioni_attivita
    //   WHERE id='%s'
    //     AND idscuola='%s'
    //     AND idcoorte='%s'
    //     AND idstatus=1
    //     AND idtipo_registrazione IN (1,3)",
    //   $args['idattivita'], $idscuola, $idcoorte );
    // $db->query( $sql );
    // $log->log( "SQL2: " . $sql );
    // $rec = $db->fetchassoc();

    // // lista prestazioni
    // if( $rec ) {
    //   $sql = sprintf( "SELECT ci.id, ci.nome as text
    //   FROM ssm.ssm_registrazioni_combo_items ci
    //   LEFT JOIN ssm.ssm_registrazioni_combo co ON co.id=ci.idcombo
    //   LEFT JOIN ssm.ssm_registrazioni_attivita sra ON JSON_CONTAINS(
    //       sra.prestazioni, %s )
    //   WHERE co.idscuola='%s' AND co.idtipo=1 AND co.idstatus=1 AND ci.idstatus=1
    //     AND JSON_CONTAINS( sra.prestazioni, %s )
    //   ORDER BY ci.nome",
    //     "concat('\"', ci.id, '\"')",
    //     $idscuola,
    //     "concat('\"', ci.id, '\"')",
    //   );
    //   $log->log( "PRESTAZIONI: " . $sql );
    //   $db->query( $sql );
    //   while( $rec = $db->fetchassoc() ) {
    //     $res['prestazioni_list'][] = $rec;
    //   }
    // }

    $res['prestazioni_list'] = prestazioni_list($args['idattivita']);

    $response->getBody()->write( json_encode( $res ) );
    return $response;
  });

/*
  $groupSpecRegistrazioni->get('/attivita/list/{idprestazione}', function (Request $request, Response $response, $args) use ($crud, $authMW) {
    $Utils = new Utils();
    $log = new Logger();
    $db = new dataBase();

    $user = $request->getAttribute('user');
    $log->log( "user: " . json_encode( $user ) );

    $sql = sprintf( "SELECT DISTINCT idattivita
      FROM ssm.ssm_registrazioni_schema
      WHERE idscuola='%s' AND idprestazione='%s' AND idstatus=1",
      $user['idscuola'], $args['idprestazione'] );
    $db->query( $sql );
    $idattivita = [];
    while( $rec = $db->fetchassoc() ) {
      $idattivita[] = $rec['idattivita'];
    }

    $log->log( "/attivita/prestazione" . $sql );
    $log->log( "/attivita/prestazione" . json_encode( $idattivita ) );

    // lista prestazioni
    $sql = sprintf( "SELECT id, nome as text
      FROM ssm.ssm_registrazioni_attivita
      WHERE id IN ('%s')", implode( "','", $idattivita ) );
    $db->query( $sql );
    while( $rec = $db->fetchassoc() ) {
      $res['attivita_list'][] = $rec;
    }

    $log->log( "/attivita/prestazione" . $sql );
    $log->log( "/attivita/prestazione" . json_encode( $res ) );

    $response->getBody()->write( json_encode( $res ) );
    return $response;
});

*/

$groupSpecRegistrazioni->get('/combo/{idattivita}/{idprestazione}', function (Request $request, Response $response, $args) use ($crud, $authMW) {
  $Utils = new Utils();
  $log = new Logger();
  $db = new dataBase();

  $user = $request->getAttribute('user');
  $specializzando = specializzando_get( $user['id'] );
  $idCoorte = $specializzando['idcoorte'];
  $idscuola = $user['idscuola'];

  $res = getComboAttivita($args['idattivita'], $args['idprestazione'], $user['idscuola'], $idCoorte);

  $response->getBody()->write( json_encode( $res ) );
  return $response;
});

function getComboAttivita($idAttivita, $idPrestazione, $idScuola, $idCoorte) {
  $log = new Logger();
  $db = new dataBase();

  $sql = sprintf(
    "SELECT combo, opzione_note, opzione_protocollo, opzione_upload
    FROM ssm.ssm_registrazioni_attivita
    WHERE id='%s'
      AND idscuola='%s'
      AND idcoorte='%s'
      AND idstatus=1
      AND idtipo_registrazione IN (1,3)",
    $db->real_escape_string( $idAttivita ),
    $db->real_escape_string( $idScuola ),
    $db->real_escape_string( $idCoorte )
  );
  $db->query($sql);
  $rec = $db->fetchassoc();

  $log->log("QUERY1: " . $sql);

  $idattivita = [];
  $combos = json_decode($rec['combo'], true);
  unset($idvalue);
  foreach ($combos as $v) {
    $v = $db->real_escape_string( $v );
    $idvalue[] = $v;
  }

  // TODO: SQL_INJECTION_TEST
  $sql = sprintf("SELECT co.id as co_id, co.nome as co_nome,
      ci.id as ci_id,
      ci.nome as ci_nome
    FROM ssm.ssm_registrazioni_combo_items ci
    LEFT JOIN ssm.ssm_registrazioni_combo co ON co.id=ci.idcombo
    WHERE co.id IN ('%s') AND co.idstatus=1
    ORDER BY co.nome,ci.nome", implode("','", $idvalue ));
  $log->log("/combo/prestazione/attivita - " . $sql);

  $o = "";
  $db->query($sql);
  while ($rec2 = $db->fetchassoc()) {
    $log->log("/combo/prestazione/attivita" . json_encode($rec2));

    if ($o != "" && $rec2['co_id'] != $o) {
      $out[] = array(
        "id" => $in[0]['co_id'],
        "nome" => $in[0]['co_nome'],
        "options" => $in
      );
      $log->log("out: " . json_encode($out));
      unset($in);
    }

    $o = $rec2['co_id'];
    $in[] = $rec2;
  }

  if ($in != "") {
      $out[] = array(
      "id" => $in[0]['co_id'],
      "nome" => $in[0]['co_nome'],
      "options" => $in
    );
  }

  // seleziona i filtri
  $sql = sprintf(
      "SELECT combo FROM ssm.ssm_registrazioni_filtri
    WHERE idscuola='%s' AND idattivita='%s' AND idprestazione='%s' AND idstatus=1",
      $db->real_escape_string( $idScuola ),
      $db->real_escape_string( $idAttivita ),
      $db->real_escape_string( $idPrestazione )
  );
  $log->log($sql);
  $db->query($sql);
  $rec = $db->fetchassoc();

  $filtro = json_decode($rec['combo'], true);

  if (sizeof($filtro) == 0) {
    if (!$out) {
        $res['autonomia_calc'] = true;
    }
    // INFO: In questo modo, quando non è presente un filtro non prendo alcuna combo esplicita.
    // Richiesta clickup #a2x2xt
    // $res['combo_list'] = $out;
    // INFO: Abbiamo aggiunto anche autonomia_calc = true per ticket: #a2x2xt
    $res['autonomia_calc'] = true;
    return $res;
  }
  foreach ($filtro as $k => $v) {
    $arFiltro[$v['idcombo']] = $v;
  }

  $log->log(json_encode($arFiltro));

  $opt = [];
  $out2 = [];
  for ($n=0; $n<sizeof($out); $n++) {
    $v = $out[$n];
    $id = $v['id'];
    if ($arFiltro[$id]) {
      for ($z=0; $z<sizeof($v['options']); $z++) {
        if (in_array($v['options'][$z]['ci_id'], $arFiltro[$id]['idvalue'])) {
            $opt[] = $v['options'][$z];
        }
      }

      if (sizeof($opt) > 0) {
        $out[$n]['options'] = $opt;
        $out2[] = $out[$n];
        $opt = [];
      }
    }
  }

  if (!$out2) {
      $res['autonomia_calc'] = true;
  }

  $res['combo_list'] = $out2;
  return $res;
}

  $groupSpecRegistrazioni->post('/autonomia/{idattivita}/{idprestazione}', function (Request $request, Response $response, $args) use ($crud, $authMW) {
      $Utils = new Utils();
      $log = new Logger();
      $db = new dataBase();

      $user = $request->getAttribute('user');
      $specializzando = specializzando_get($user['id']);

      $idcoorte = $specializzando['idcoorte'];
      $idscuola = $user['idscuola'];

      $combo = json_decode( $request->getBody(), true);

      $autonomia = autonomiaGet($user['id'], $combo, $idscuola, $idcoorte, $args['idprestazione']);

      $response->getBody()->write(json_encode($autonomia));
      return $response;

  });

  function autonomiaGet($idUser, $combo, $idScuola, $idCoorte, $idPrestazione)  {
    $log = new Logger();

    $log->log($idPrestazione);
    $contatori = searchContatori($combo, $idScuola, $idCoorte, $idPrestazione, $idUser);

    // Forse non serve più, è una cosa vecchia, fatta durante il primo sviluppo della funzionalità
    $contatore = searchContatoreAutonomia($contatori);

    $log->log("Contatore " . json_encode($contatore));

    $contatore_count = search_contatore_attivita($idUser, $idCoorte, $contatore['id']);

    $log->log("Count: " . $contatore_count);
    $autonomia = [];

    foreach ($contatore['autonomia'] as $k => $v) {
        // INFO: Facciamo -1 perchè quando stai inserendo l'ennesima, quella è già la (livello da).
        // Quindi quando ne ho ($contatore_count = 1) se livello_da = 2 quella che sto inserendo è già la 2
        if ($contatore_count >= $v['livello_da'] - 1) {
          $autonomia[] = array(
            "id" => $v['autonomia'],
            "text" => autonomia_text($v['autonomia'])
          );
        }
    }

    // Ordino l'autonomia per id
    function cmp($a, $b)
    {
        return strcmp($a['id'], $b['id']);
    }
    usort($autonomia, "cmp");
    return $autonomia;
  }

  // aggiorna lo stato della registrazione
  $groupSpecRegistrazioni->post('/set_status/all/{conferma_stato}', function (Request $request, Response $response, $args) use ($crud) {
    $log = new Logger();
    $db = new dataBase();

    $user = $request->getAttribute('user');
    $p = $request->getQueryParams();

    // TODO: Aggiungere l'anno per confermare le registrazioni solo di quell'anno!!
    if ($user['idruolo'] == 8 && $args['conferma_stato'] == 1)  {
      $sql = sprintf("UPDATE ssm_registrazioni SET conferma_stato=1 WHERE idutente='%s' AND conferma_stato = 0", $db->real_escape_string( $user['id'] ) );
      $res['success'] = $db->query($sql);
    } else if ($args['conferma_stato'] == 2 && isset($p['idSpecializzando'])) {
      if ($user['idruolo'] == 7) {
        $res['success'] = registrazioni_tutor_confirm($user['id'], $p['idSpecializzando'], $p['trainerTutor'], $p);
      } else if ($user['idruolo'] == 5) {
        $res['success'] = registrazioni_direttore_confirm($user['id'], $p['idSpecializzando'], $p);
      }
    }

    if( $res['success'] == 1 ) {
      $response->getBody()->write( json_encode( $res ) );
        return $response
          ->withStatus(200)
          ->withHeader('Content-Type', 'application/json');
    } else {
        $response->getBody()->write( "Errore aggiornamento" );
        return $response
          ->withStatus(400)
          ->withHeader('Content-Type', 'text/plain');
    }
  });

  $groupSpecRegistrazioni->post('/set_status/{id}/{idstatus}', function (Request $request, Response $response, $args) use ($crud) {
    $log = new Logger();
    $Utils = new Utils();
    $user = $request->getAttribute('user');

    $update = array(
      "conferma_stato" => $args['idstatus'],
      "conferma_data" => "now()"
    );

    if ($args['idstatus'] == 2) {
      $update['conferma_utente'] = $user['id'];
      $update['conferma_idruolo'] = $user['idruolo'];
    }

    $res = $Utils->dbSql( false, "ssm_registrazioni", $update, "id", $args['id'] );
    if( $res['success'] == 1 ) {
      $response->getBody()->write( json_encode( $res ) );
        return $response
          ->withStatus(200)
          ->withHeader('Content-Type', 'application/json');
    } else {
        $response->getBody()->write( "Errore aggiornamento" );
        return $response
          ->withStatus(400)
          ->withHeader('Content-Type', 'text/plain');
    }
  });
})->add( $authMW );

function attivita_list($idscuola, $idcoorte) {
  $db = new dataBase();
  $sql = sprintf(
    "SELECT id, nome as text, opzione_note, opzione_protocollo, opzione_upload, idtipo_registrazione
      FROM ssm.ssm_registrazioni_attivita
      WHERE idscuola='%s'
        AND idcoorte='%s'
        AND idstatus=1
      ORDER BY nome",
    $db->real_escape_string( $idscuola ),
    $db->real_escape_string( $idcoorte )
  );
  $db->query($sql);
  while ($rec = $db->fetchassoc()) {
      $ar[] = $rec;
  }

  return $ar;
}

function comboImpliciteCalc($struttura, $idAttivita, $idPrestazione)  {
  $log = new Logger();
  $schemaAttivita = schemaAttivitaGet($idAttivita, $idPrestazione);
  foreach ($schemaAttivita as $value) {
    $found = true;
    $empty = true;
    $value['combo'] = json_decode($value['combo'], true);
    foreach ($value['combo'] as $v)  {
      if ($v['tipo'] == 'I') {
          continue;
      }
      $empty = false;
      $oneFound = false;
      foreach ($struttura as $s) {
          // Se non rispecchia le combo
          $log->log(json_encode($s) . " ------------- " . json_encode($v));
          if (($s['id'] == $v['idcombo'] && $s['idvalue'] == $v['idvalue'])) {
            $oneFound = true;
          }
      }
      if (!$oneFound) {
        $found = false;
        break;
      }
    }
    if ($found || $empty) {
      $struttura = formatComboImplicite($struttura, $value['combo']);
    }
  }
  return $struttura;
}

function formatComboImplicite($struttura, $combo)  {
  $ret = array();
  foreach ($combo as $value) {
    if ($value['tipo'] == "E") {
      continue;
    }
    $ret[] = array(
      "id" => substr($value['idcombo'], 4),
      "idvalue" => $value['idvalue']
    );
  }
  $ret = array_merge($struttura, $ret);
  return $ret;
}

function schemaAttivitaGet($idAttivita, $idPrestazione) {
  $utils = new Utils();
  $arSql = array(
    "select" => ["combo"],
    "from" => "ssm.ssm_registrazioni_schema",
    "where" => [
      [
        "field" => "idattivita",
        "value" => $idAttivita,
        "operatorAfter" => "AND"
      ],
      [
        "field" => "idprestazione",
        "value" => $idPrestazione,
        "operatorAfter" => "AND"
      ],
      [
        "field" => "idstatus",
        "value" => 1
      ]
    ]
  );

  $arrSql = $utils->dbSelect($arSql);
  return $arrSql['data'];
}

function prestazioni_list($idattivita) {
  $db = new dataBase();
  $sql = sprintf("SELECT prestazioni FROM ssm.ssm_registrazioni_attivita WHERE id = '%s' AND idstatus=1", $db->real_escape_string( $idattivita ));
  $db->query($sql);
  $rec = $db->fetchassoc();

  $prestazioni = json_decode($rec['prestazioni'], true);
  $prestazioniIn = "";
  foreach ($prestazioni as $value) {
    $value = $db->real_escape_string( $value );
    $prestazioniIn .= "'$value',";
  }
  $prestazioniIn = substr($prestazioniIn, 0, -1);

  // TODO: SQL_INJECTION_TEST
  $sql = sprintf("SELECT
                    ci.id,
                    ci.nome AS text
                  FROM ssm.ssm_registrazioni_combo_items ci
                  WHERE id IN(%s) AND idstatus=1
                  ORDER BY ci.nome ASC", $prestazioniIn);
  $db->query($sql);
  while ($rec = $db->fetchassoc()) {
      $ret[] = $rec;
  }
  return $ret;
}

function searchContatori ( $req, $idscuola = '', $idcoorte = '', $idprestazione = null, $idUser = '' ) {
  $db = new dataBase();
  $log = new Logger();

  // $req[] = array( "idcombo" => "30605e5e-f7c7-4021-bc0b-bcf8e25febe7",
  //   "idvalue" => "835613c2-83df-4e17-af07-dc34bb176d5c" );

  // Aggiungo al filtro dei where l'idprestazione selezionato
  $idcombo_prestazione = _get_id_combo_prestazione($idscuola);
  $arWhere[] = sprintf(
      "( JSON_CONTAINS( JSON_EXTRACT(struttura, '$[*].idvalue'), '\"%s\"', '$' )
    AND JSON_CONTAINS( JSON_EXTRACT(struttura, '$[*].id'), '\"%s\"', '$' ) )",
      $db->real_escape_string( $idprestazione ),
      $db->real_escape_string( $idcombo_prestazione )
  );

  foreach ($req as $key => $value) {
    $arWhere[] = sprintf("( JSON_CONTAINS( JSON_EXTRACT(struttura, '$[*].idvalue'), '\"%s\"', '$' )
      AND JSON_CONTAINS( JSON_EXTRACT(struttura, '$[*].id'), '\"%s\"', '$' ) )",
      $db->real_escape_string( $value['idvalue'] ), $db->real_escape_string( $value['id'] ));
  }

  $s = implode( " AND ", $arWhere );
  // TODO: SQL_INJECTION_TEST
  $sql = sprintf("SELECT id, autonomia
    FROM ssm.ssm_pds_coorti_contatori
    WHERE idscuola_specializzazione='%s' AND idcoorte='%s' AND %s AND idstatus=1", $db->real_escape_string( $idscuola ), $db->real_escape_string( $idcoorte ), $s);

  $log->log( $sql );

  $db->query( $sql );
  $ar = array();
  while($rec = $db->fetchassoc()) {
    $rec['autonomia'] = json_decode($rec['autonomia'], true);
    $ar[] = $rec;
  }

  // Cerco se ci sono filtri per il contatore trovato
  $sql = sprintf("SELECT livello_da, livello_a, autonomia, idcontatore as id
    FROM ssm.ssm_pds_registrazioni_filtri
    WHERE idcontatore='%s' AND idspecializzando='%s' AND idstatus=1", $ar[0]['id'], $idUser);
  $log->log($sql);

  $db->query( $sql );
  $arFilters = array(
    "autonomia" => []
  );
  while($rec = $db->fetchassoc()) {
    $autonomia = array(
      "livello_da" => $rec['livello_da'],
      "livello_a" => $rec['livello_a'],
      "autonomia" => $rec['autonomia']
    );
    $arFilters['autonomia'][] = $autonomia;
    // FIXME: Brutto!
    $arFilters['id'] = $rec['id'];
  }
  // Qui torno un array perchè il sistema funzionava con molteplici contatori e poi è stato cambiato,
  // andrebbe rivista la funzionalità...
  if (count($arFilters['autonomia']) > 0)  {
    return array($arFilters);
  } else {
    return $ar;
  }
}

function search_contatore_attivita( $idspecializzando, $idcoorte, $idcontatore ) {
  $db = new dataBase();

  // TODO: SQL_INJECTION_TEST
  $sql = sprintf( "SELECT SUM(quantita) as num_contatori
    FROM ssm_registrazioni
    WHERE idutente='%s'
      AND idcoorte='%s'
      AND idstatus=1
      AND conferma_stato != 3
      AND JSON_CONTAINS( contatori, '\"%s\"', '$' )",
    $db->real_escape_string( $idspecializzando ), $db->real_escape_string( $idcoorte ), $db->real_escape_string( $idcontatore ) );
  $db->query( $sql );
  $rec = $db->fetchassoc();
  if ($rec['num_contatori'] == "")
    $rec['num_contatori'] = 0;
  return $rec['num_contatori'];
}

function searchContatoreAutonomia ( $contatori )  {
  foreach ($contatori as $v) {
    if (sizeof($v['autonomia']) > 0) {
      $contatore = $v;
    }
  }
  return $contatore;
}

function specializzando_get( $idspecializzando ) {
  $log = new Logger();
  $db = new dataBase();
  $sql = sprintf( "SELECT su.id, su.idcoorte, su.anno_scuola, su.nome, su.cognome, rl.idscuola
    FROM ssm_utenti su
    LEFT JOIN ssm_utenti_ruoli_lista rl ON rl.idutente=su.id
    WHERE su.id='%s'", $db->real_escape_string( $idspecializzando ) );
  // $log->log( "SQL: " . $sql );
  $db->query( $sql );
  $rec = $db->fetchassoc();
  // $log->log( "SQL: " . json_encode( $rec ) );
  return $rec;
}

function turni_specializzando_get( $idspecializzando ) {
  $log = new Logger();
  $db = new dataBase();
  $sql = sprintf( "SELECT * FROM ssm_turni WHERE idspecializzando='%s'", $db->real_escape_string( $idspecializzando ) );

  $log->log( "Turni specializzando: " . $sql );
  $db->query( $sql );
  $rec = $db->fetchassoc();
  return $rec;
}

function turni_specializzando_list( $idspecializzando, $idScuola, $anno ) {
  $log = new Logger();
  $db = new dataBase();
  // INFO: Qui filtriamo perchè ci è stato chiesto di permettere la creazione di turni anche senza UO, ma di non calcolare il turno quindi in FE
  $sql = sprintf( "SELECT * FROM ssm_turni WHERE idspecializzando='%s' AND idscuola = '%s' AND anno = %d AND idstatus=1 AND ( idunita is not null AND idunita != '' )", $db->real_escape_string( $idspecializzando ), $db->real_escape_string( $idScuola ), $anno );

  $log->log( "Turni specializzando: " . $sql );
  $db->query( $sql );
  while( $rec = $db->fetchassoc() ) {
    $ar[] = $rec;
  }
  return $ar;
}


function unita_info( $idunita ) {
  $db = new dataBase();
  $log = new Logger();

  // tutor dell'unità
  $sql = sprintf( "SELECT ut.id, concat(ut.nome, ' ', ut.cognome) as text
    FROM ssm_utenti_ruoli_lista url
    LEFT JOIN ssm_utenti ut ON ut.id=url.idutente
    WHERE url.idruolo=7 AND url.idunita='%s' AND url.idstatus=1 AND ut.idstatus=1
    ORDER BY ut.cognome, ut.nome",
    $db->real_escape_string( $idunita ) );
  $log->log( "TUTOR: " . $sql );

  $db->query( $sql );
  while( $rec = $db->fetchassoc() ) {
    $res['tutor_list'][] = $rec;
  }

  // direttori dell'unità
  $sql = sprintf( "SELECT ut.id, concat(ut.nome, ' ', ut.cognome) as text
    FROM ssm_utenti_ruoli_lista url
    LEFT JOIN ssm_utenti ut ON ut.id=url.idutente
    WHERE url.idruolo=6 AND url.idunita='%s' AND url.idstatus=1
    ORDER BY ut.cognome, ut.nome",
  $db->real_escape_string( $idunita ) );
  $log->log( "DIRETTORE: " . $sql );
  $db->query( $sql );
  $rec = $db->fetchassoc();
  $res['direttore'] = $rec['text'];

  return $res;
}

function registrazioni_tutor_get( $idTutor, $paging, $idspecializzando, $trainerTutor )  {
  $db = new dataBase();
  $log = new Logger();

  $trainer = sprintf("SELECT
            DISTINCT *, 1 as idtipo_tutor
            FROM ssm_registrazioni
            WHERE idtutor = '%s' AND idutente = '%s' AND idstatus = 1", $db->real_escape_string( $idTutor ), $db->real_escape_string( $idspecializzando ) );
  $tutor = sprintf( "( SELECT
          sr.*, 2 as idtipo_tutor
          FROM
            ssm_registrazioni sr
          INNER JOIN
            ssm_turni st ON (sr.idutente = st.idspecializzando
            AND sr.data_registrazione >= st.data_inizio
            AND sr.data_registrazione <= st.data_fine
            AND st.idstatus=1)
          WHERE
            st.idtutor = '%s' AND sr.idutente = '%s' AND sr.idstatus = 1)", $db->real_escape_string( $idTutor ), $db->real_escape_string( $idspecializzando ));
  switch ($trainerTutor) {
    case 0:
      $tt = "$trainer UNION $tutor";
      break;
    case 1:
      $tt = "$trainer";
      break;
    case 2:
      $tt = "$tutor";
      break;
  }

  $arWhere = array();
  $sWhere = '';
  if ($paging['s'])  {
    $arWhere[] = "ci.nome LIKE '%%" . $db->real_escape_string( $paging['s'] ) . "%%'";
  }
  if ($paging['idprestazione'])  {
    $arWhere[] = "idprestazione='" . $db->real_escape_string( $paging['idprestazione'] ) . "'";
  }
  if ($paging['idattivita'])  {
    $arWhere[] = "idattivita='" . $db->real_escape_string( $paging['idattivita'] ) . "'";
  }
  if ($paging['data_registrazione'])  {
    $arWhere[] = "data_registrazione='" . $db->real_escape_string( $paging['data_registrazione'] ) . "'";
  }

  if (count($arWhere) > 0) {
      $sWhere = "AND " . implode(" AND ", $arWhere);
  }

  // TODO: SQL_INJECTION_TEST
  $sql = sprintf(
        "SELECT DISTINCT SQL_CALC_FOUND_ROWS
          s.id,
          s.idtipo_tutor,
          idprestazione,
          idattivita,
          date_format(data_registrazione,'%%d-%%m-%%Y') as data_registrazione_text,
          data_registrazione,
          quantita,
          struttura_text,
          CONCAT(su.nome, ' ', su.cognome) as tutor_nome,
          ra.nome as attivita_text,
          ci.nome as prestazione_text,
          s.struttura as combo,
          conferma_stato,
          case conferma_stato
          when 0 then 'Da inviare'
          when 1 then 'Inviato'
          when 2 then 'Confermato'
          when 3 then 'Scartato'
          end as conferma_stato_text,
          case conferma_stato
          when 1 then 'Da confermare'
          when 2 then 'Confermato'
          when 3 then 'Scartato'
          end as conferma_stato_tutor_text,
          case conferma_stato
          when 0 then '#cc0000'
          when 1 then '#032b6b'
          when 2 then '#258e7a'
          when 3 then '#6e6e6e'
          end as button_color FROM (
        %s
        ) AS s
          LEFT JOIN ssm.ssm_registrazioni_attivita ra ON ra.id=s.idattivita
	        LEFT JOIN ssm.ssm_registrazioni_combo_items ci ON ci.id=s.idprestazione
	        LEFT JOIN ssm_utenti su ON su.id=s.idtutor
          WHERE s.conferma_stato > 0
          %s
          ORDER BY %s %s
          LIMIT %d, %d", $tt, $sWhere, $db->real_escape_string( $paging['srt'] ), $db->real_escape_string( $paging['o'] ), ($paging['p'] - 1) * $paging['c'], $paging['c']);

  $db->query($sql);
  $log->log($sql);

  $ar = array();
  while( $rec = $db->fetchassoc() ) {
    $ar[] = $rec;
  }
  $db->query("SELECT FOUND_ROWS()");
  $ret['total'] = intval($db->fetcharray()[0]);
  $ret['count'] = sizeof($ar);
  $ret['rows'] = $ar;
  return $ret;
}

function registrazioni_tutor_confirm($idTutor, $idSpecializzando, $trainerTutor, $p) {
  $log = new Logger();
  $db = new dataBase();

  $arWhere = [];
  if ($p['idprestazione']) {
    $arWhere[] = "sr.idprestazione='" . $db->real_escape_string($p['idprestazione']) . "'";
  }
  if ($p['idattivita']) {
    $arWhere[] = "sr.idattivita='" . $db->real_escape_string($p['idattivita']) . "'";
  }
  if ($p['data_registrazione']) {
    $arWhere[] = "sr.data_registrazione='" . $db->real_escape_string($p['data_registrazione']) . "'";
  }
  if (count($arWhere) > 0) {
    $sWhere = "AND " . implode(" AND ", $arWhere);
  } else {
    $sWhere = '';
  }

  if ($trainerTutor == 1) {
      // TODO: TrainerTutor
      $trainer = sprintf("UPDATE
      ssm_registrazioni sr
      SET conferma_stato = 2, conferma_utente = '%s', conferma_idruolo = 7
      WHERE idtutor = '%s'
      AND idutente = '%s'
      AND idstatus = 1
      AND conferma_stato = 1 %s", $db->real_escape_string($idTutor), $db->real_escape_string($idTutor), $db->real_escape_string($idSpecializzando), $sWhere);
      $db->query($trainer);
  }
  if ($trainerTutor == 2) {
      $tutor = sprintf(
          "UPDATE
      ssm_registrazioni sr
      INNER JOIN
      ssm_turni st ON (sr.idutente = st.idspecializzando
        AND sr.data_registrazione >= st.data_inizio
        AND sr.data_registrazione <= st.data_fine
        AND st.idstatus=1)
      SET conferma_stato=2, conferma_utente = '%s', conferma_idruolo = 7
      WHERE
      st.idtutor = '%s'
      AND sr.idutente = '%s'
      AND sr.idstatus = 1
      AND sr.conferma_stato = 1 %s",
          $db->real_escape_string($idTutor),
          $db->real_escape_string($idTutor),
          $db->real_escape_string($idSpecializzando),
          $sWhere
      );
      $log->log($tutor);
      $db->query($tutor);
  }
  return true;
}

function registrazioni_direttore_confirm($idUser, $idSpecializzando, $p) {
  $log = new Logger();
  $db = new dataBase();

  $arWhere = [];
  if ($p['idprestazione']) {
      $arWhere[] = "idprestazione='" . $db->real_escape_string($p['idprestazione']) . "'";
  }
  if ($p['idattivita']) {
      $arWhere[] = "idattivita='" . $db->real_escape_string($p['idattivita']) . "'";
  }
  if ($p['data_registrazione']) {
      $arWhere[] = "data_registrazione='" . $db->real_escape_string($p['data_registrazione']) . "'";
  }
  if ($p['idanno'] != 0) {
    $arWhere[] = sprintf("anno=%d", $p['idanno']);
  }
  if ($p['idcoorte']) {
    $arWhere[] = sprintf("idcoorte='%s'", $db->real_escape_string($p['idcoorte']));
  }
  if (count($arWhere) > 0) {
      $sWhere = "AND " . implode(" AND ", $arWhere);
  } else {
      $sWhere = '';
  }

  $sql = sprintf("UPDATE
    ssm_registrazioni
    SET conferma_stato = 2, conferma_utente = '%s', conferma_idruolo=5
    WHERE idutente = '%s'
    AND idstatus = 1
    AND conferma_stato = 1 %s", $db->real_escape_string( $idUser ), $db->real_escape_string( $idSpecializzando ), $sWhere);
  $log->log($sql);
  $db->query($sql);
  return true;
}

function registrazioni_dir_get( $idSpecializzando, $idScuola, $paging )  {
  $db = new dataBase();
  $log = new Logger();

  $s = '';
  if ($paging['idcoorte']) {
    $src[] = sprintf("idcoorte='%s'", $db->real_escape_string( $paging['idcoorte'] ));
  }

  if ($paging['idattivita']) {
    $src[] = sprintf("s.idattivita='%s'", $db->real_escape_string( $paging['idattivita'] ) );
  }
  if ($paging['idprestazione']) {
    $src[] = sprintf("s.idprestazione='%s'", $db->real_escape_string( $paging['idprestazione'] ) );
  }
  if ($paging['data_registrazione']) {
    $src[] = sprintf("s.data_registrazione='%s'", $db->real_escape_string( $paging['data_registrazione'] ) );
  }
  if ($paging['s']) {
    $src[] = sprintf("(ci.nome like '%%%s%%' OR ra.nome like '%%%s%%' )", $db->real_escape_string( $paging['s'] ), $db->real_escape_string( $paging['s'] ) );
  }

  if ($paging['idanno']) {
    $src[] = sprintf("anno='%s'", $db->real_escape_string( $paging['idanno'] ) );
  }
  if (sizeof($src) > 0) {
    $s .= "AND ";
    $s .= implode(" AND ", $src);
  }

  $sql = sprintf(
        "SELECT DISTINCT SQL_CALC_FOUND_ROWS
          s.id,
          date_format(data_registrazione,'%%d-%%m-%%Y') as data_registrazione_text,
          data_registrazione,
          quantita,
          struttura_text,
          ra.nome as attivita_text,
          ci.nome as prestazione_text,
          s.struttura as combo,
          CONCAT(su.nome, ' ', su.cognome) as tutor_nome,
          conferma_stato,
          case conferma_stato
          when 0 then 'Da inviare'
          when 1 then 'Inviato'
          when 2 then 'Confermato'
          when 3 then 'Scartato'
          end as conferma_stato_text,
          case conferma_stato
          when 1 then 'Da confermare'
          when 2 then 'Confermato'
          when 3 then 'Scartato'
          end as conferma_stato_tutor_text,
          case conferma_stato
          when 0 then '#cc0000'
          when 1 then '#032b6b'
          when 2 then '#258e7a'
          when 3 then '#6e6e6e'
          end as button_color
        FROM ssm_registrazioni s
        LEFT JOIN ssm.ssm_registrazioni_attivita ra ON ra.id=s.idattivita
        LEFT JOIN ssm.ssm_registrazioni_combo_items ci ON ci.id=s.idprestazione
        LEFT JOIN ssm_utenti su ON su.id=s.idtutor
        WHERE s.conferma_stato > 0 AND s.idutente = '%s' AND s.idstatus = 1 %s
        ORDER BY %s %s
        LIMIT %d, %d", $db->real_escape_string( $idSpecializzando ), $s, $db->real_escape_string( $paging['srt'] ), $db->real_escape_string( $paging['o'] ), ($paging['p'] - 1) * $paging['c'], $paging['c']);

  $log->log($sql);
  $db->query($sql);

  $ar = array();
  while( $rec = $db->fetchassoc() ) {
    $ar[] = $rec;
  }
  $db->query("SELECT FOUND_ROWS()");
  $ret['total'] = intval($db->fetcharray()[0]);
  $ret['count'] = sizeof($ar);
  $ret['rows'] = $ar;
  return $ret;
}

function _get_id_combo_prestazione($idscuola) {
  $db = new dataBase();

  $sql = sprintf("SELECT id FROM ssm.ssm_registrazioni_combo WHERE idscuola = '%s' AND idtipo = 1 AND idstatus = 1", $db->real_escape_string( $idscuola ) );
  $db->query($sql);

  $ret = $db->fetchassoc();
  return $ret['id'];
}

// FIXME:
function autonomia_text( $autonomia ) {
  switch ($autonomia) {
    case 1:
      return "Attività in appoggio";
      break;
    case 2:
      return "Attività in collaborazione guidata";
      break;
    case 3:
      return "Attività in autonomia protetta";
      break;
    case 4:
      return "Serve un testo per 4";
      break;
    case 5:
      return "Serve un testo per 5";
      break;
  }
}

function getAttivitaFields( $idAttivita ) {
  $db = new dataBase();
  $sql = sprintf(
    "SELECT opzione_note, opzione_protocollo, opzione_upload, idtipo_registrazione
      FROM ssm.ssm_registrazioni_attivita
      WHERE id='%s'
        AND idstatus=1",
        $db->real_escape_string( $idAttivita )
    );
  $db->query($sql);
  $rec = $db->fetchassoc();
  return $rec;
}

?>

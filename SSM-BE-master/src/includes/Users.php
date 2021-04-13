<?


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



$app->group('/users', function (RouteCollectorProxy $groupUsers) use ($auth) {

  $data = [
    "table" => "ssm_utenti",
    "id" => "id",
    "sort" => "concat(cognome, ' ', nome)",
    "order" => "asc",
    "status_field" => "idstatus",
    "update_field" => "date_update",
    "create_field" => "date_create",
    "list_fields" => [
      "ssm_utenti.id", "concat(ssm_utenti.cognome, ' ', ssm_utenti.nome) as nome_cognome",
      "ssm_utenti.codice_fiscale",
      "ssm_utenti.matricola", "ssm_utenti.email",
      "pc.nome as coorte_text",
      "ssm_utenti.anno_scuola"
     ],
    "list_join" => [
      [
          "ssm.ssm_pds_coorti pc",
          " pc.id=ssm_utenti.idcoorte "
      ]
    ]
  ];
  $crud = new CRUD( $data );


  // list
  $groupUsers->get('', function (Request $request, Response $response) use ($auth, $crud) {
      $Utils = new Utils();
      $p = $request->getQueryParams();

      if( $p['srt'] == "nome_cognome" )
        $p['srt'] = "ssm_utenti.cognome,ssm_utenti.nome";

      $p['_ssm_utenti.idstatus'] = "1";

      if( $p['s'] != "" ) {
        $p['multi_search'] = array(
          [
            "field" => "ssm_utenti.cognome",
            "operator" => " LIKE ",
            "value" => "%" . $p['s'] . "%",
            "operatorAfter" => " OR "
          ],
          [
            "field" => "ssm_utenti.nome",
            "operator" => " LIKE ",
            "value" => "%" . $p['s'] . "%",
            "operatorAfter" => " OR "
          ],
          [
            "field" => "ssm_utenti.codice_fiscale",
            "operator" => " LIKE ",
            "value" => "%" . $p['s'] . "%",
            "operatorAfter" => " OR "
          ],
          [
            "field" => "email",
            "operator" => " LIKE ",
            "value" => "%" . $p['s'] . "%",
          ]
        );
      }

      $res = $crud->record_list( $p );



      /*
      $ar = array( "table" => "Profili", "value" => "id", "text" => "nome_profilo", "order" => "nome_profilo" );
      $res['profili_list'] = $Utils->_combo_list( $ar, true, "" );

      $ar = array( "table" => "Scuola_specializzazione", "value" => "id", "text" => "nome_scuola", "order" => "nome_scuola" );
      $res['scuole_list'] = $Utils->_combo_list( $ar, true, "" );
      */

      /*
      $log = new Logger();
      $log->log( "Utenti: " . json_encode( $res ) );
      */

      $response->getBody()->write( json_encode( $res ) );
      return $response;
  });

  // get
  $groupUsers->get('/{id}', function (Request $request, Response $response, $args) use ($crud) {
      $log = new Logger();
      $Utils = new Utils();

      $res = $crud->record_get( $args['id'] )['data'][0];
      if( !$res )
        $res = [];

      // rimuove la password al get
      unset( $res['password'] );

      $log->log( "users get " . json_encode( $res ) );

      $res['ruoli'] = utente_ruoli_lista_get( $args['id'] );

      $ar = array( "table" => "ssm.ssm_utenti_ruoli", "value" => "id", "text" => "nome", "order" => "id" );
      $res['ruoli_list'] = $Utils->_combo_list( $ar, true, "" );

      $ar = array( "table" => "ssm_utenti_titoli", "value" => "id", "text" => "nome_titolo", "order" => "ordine" );
      $res['titoli_list'] = $Utils->_combo_list( $ar, true, "" );

      $ar = array( "table" => "ssm.ssm_specializzando_status", "value" => "id", "text" => "stato", "order" => "id" );
      $res['stati_list'] = $Utils->_combo_list( $ar, true, "" );

      $response->getBody()->write( json_encode( $res ) );
      return $response;
  });


  // get
  $groupUsers->get('/ruolo/{id}', function (Request $request, Response $response, $args) use ($crud) {
    $log = new Logger();
    $Utils = new Utils();
    $db = new dataBase();

    $p = $request->getQueryParams();
    $log->log( "ruolo: " . json_encode( $p ) );

    switch( $p['idruolo'] ) {
      case 2: // ateneo
      case 3: //
      case 4: //
      case 10: // docente
          $ar = array( "table" => "ssm.ssm_atenei", "value" => "id", "text" => "nome_ateneo", "order" => "nome_ateneo", "where" => "idstatus=1" );
        $res['nome_list'] = "atenei";
        $res['data'] = $Utils->_combo_list( $ar, true, "" );

        if( $p['idruolo'] == 10 && $p['idateneo'] > 0 ) {
          $res['nome_list'] = "settori_scientifici";
          $ar = array( "table" => "ssm.ssm_settori_scientifici", "value" => "id", "text" => "nome", "order" => "nome" );
          $res['data'] = $Utils->_combo_list($ar, true, "");
        }
      break;


      case 5: // elenco scuole dell'ateneo selezionato
      case 8:
      case 9:
      case 11: // studente
      case 12: // segreteria tirocinio
      case 13: // segreteria tirocinio
        if( $p['idateneo'] == "" ) {
          $ar = array( "table" => "ssm.ssm_atenei", "value" => "id", "text" => "nome_ateneo", "order" => "nome_ateneo", "where" => "idstatus=1" );
          $res['nome_list'] = "atenei";
          $res['data'] = $Utils->_combo_list( $ar, true, "" );
        } elseif( $p['idscuola'] == "" ) {
          $sql = sprintf( "SELECT sa.id as id, s.nome_scuola as text
          FROM ssm.ssm_scuole_atenei sa
          LEFT JOIN ssm.ssm_scuole s on s.id=sa.idscuola
          LEFT JOIN ssm.ssm_atenei a on a.id=sa.idateneo
          WHERE sa.idateneo='%s' AND sa.idstatus=1
          ORDER BY s.nome_scuola", $db->real_escape_string( $p['idateneo'] ) );
          $db->query( $sql );
          while( $rec = $db->fetchassoc() ) {
            $ar[] = $rec;
          }
          $res['nome_list'] = "scuole";
          $res['data'] = $ar;
          $log->log( "::: " . $sql );

        } elseif( $p['idcoorte'] == "" ) {
          $log->log( "COORTI " . json_encode( $p ) );
          if( $p['idruolo'] == 8 ) {
            // coorti
            $sql = sprintf( "SELECT id, nome as text
              FROM ssm.ssm_pds_coorti
              WHERE idscuola_specializzazione='%s'
                AND idstatus=1",
            $db->real_escape_string( $p['idscuola'] ) );
            $db->query( $sql );
            while( $rec = $db->fetchassoc() ) {
              $ar[] = $rec;
            }
            $res['nome_list'] = "coorti";
            $res['data'] = $ar;
          }
        } else {
          $anni = scuola_anni_get( $p['idscuola'] );
          $log->log( "NUMERO ANNI: " . $anni );
          for( $n=1; $n<=$anni; $n++ ) {
            $ar[] = array( "id" => $n, "text" => "Anno " . $n );
          }
          $res['nome_list'] = "anni";
          $res['data'] = $ar;
        }



        break;
/*

        break;
*/
      case 6:
      case 7:
        if( $p['idpresidio'] == "" ) {
          $ar = array( "table" => "ssm.ssm_presidi p left join ssm.ssm_aziende a ON a.id=p.idazienda ", "value" => "p.id", "text" => "CONCAT(p.nome, ' - ', a.nome)", "order" => "p.nome", "where" => "p.idstatus=1" );
          $res['nome_list'] = "presidi";
          $res['data'] = $Utils->_combo_list( $ar, true, true );
        } else {
          $where = sprintf( "idstatus=1 AND idpresidio='%s'", $p['idpresidio'] );
          $ar = array( "table" => "ssm.ssm_unita_operative", "value" => "id", "text" => "nome", "order" => "nome", "where" => $where );
          $res['nome_list'] = "unita";
          $res['data'] = $Utils->_combo_list( $ar, "", true );
          $log->log( "data: " . json_encode( $res ) );
        }
      break;



    }


    $response->getBody()->write( json_encode( $res ) );
    return $response;
  });

  // put
  $groupUsers->put('/ruolo/{idutente}', function (Request $request, Response $response, $args) use ($crud) {
    $log = new Logger();
    $Utils = new Utils();
    $db = new dataBase();

    $p = json_decode($request->getBody(), true);
    $log->log( "ruolo_set: " . $args['idutente'] . " - " . json_encode( $p ) );

    //$arSS = json_decode( $p['idsettore_scientifico'], true );
    //$log->log( "SS: " . json_encode( $arSS ) );
    $sql = sprintf( "DELETE FROM ssm.ssm_utenti_settori_scientifici WHERE idutente='%s'", $db->real_escape_string( $args['idutente'] ) );
    $db->query( $sql );

    foreach( $p['idsettore_scientifico'] as $v ) {
      $ar = array( "idutente" => $args['idutente'], "idsettore_scientifico" => $v );
      $ret = $Utils->dbSql( true, "ssm_utenti_settori_scientifici", $ar, "", "" );
      $log->log( "ss - " . json_encode( $ret ) );
    }
    unset( $p['idsettore_scientifico'] );

    $arUpdate['ruoli'] = json_encode( $p );
    $arUpdate['idcoorte'] = $p['idcoorte'];

    if( $p['anno'] != "" )
      $arUpdate['anno_scuola'] = $p['anno'];
    unset( $p['idcoorte'] );
    unset( $p['anno'] );


    utente_ruoli_set( $args['idutente'], $p );

    if( $p['data_contratto'] != '' )
      $arUpdate['data_contratto'] = substr( $p['data_contratto'], 0, 10 );
    else
      unset( $p['data_contratto'] );

    $res = $Utils->dbSql( false, "ssm_utenti", $arUpdate, "id", $args['idutente'] );
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


  // new
  $groupUsers->put('', function (Request $request, Response $response) use ($crud ) {
    $p = json_decode($request->getBody(), true);

    unset( $p['idruolo_amministrativo'] );
    unset( $p['idorgano'] );

    $retValidate = validate( "utenti", $p );
    if( $retValidate != "" ) {
      $response->getBody()->write( $retValidate );
      return $response
        ->withStatus(400)
        ->withHeader('Content-Type', 'text/plain');
    }

    if( $p['password'] != "" ) {
      $p['password'] = password_hash( $p['password'], PASSWORD_BCRYPT );
    }

    $p['data_nascita'] = $p['data_nascita'] == "" ? "0000-00-00" : substr( $p['data_nascita'], 0, 10);
    $p['data_contratto'] = $p['data_contratto'] == "" ? "0000-00-00" : substr( $p['data_contratto'], 0, 10);

    $res = $crud->record_new( $p );
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
  $groupUsers->post('/{id}', function (Request $request, Response $response, $args) use ($crud) {
    $log = new Logger();

    $p = json_decode($request->getBody(), true);
    $log->log( "update utente - " . json_encode( $p ) );

    unset( $p['idruolo_amministrativo'] );
    unset( $p['idorgano'] );

    if( $p['password'] != "" ) {
      $p['password'] = password_hash( $p['password'], PASSWORD_BCRYPT );
    }

    $p['data_contratto'] = substr( $p['data_contratto'], 0, 10 );

    $retValidate = validate( "utenti", $p );
    if( $retValidate != "" ) {
      $response->getBody()->write( $retValidate );
      return $response
        ->withStatus(400)
        ->withHeader('Content-Type', 'text/plain');
    }

    $res = $crud->record_update( $args['id'], $p );
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
  $groupUsers->delete('/{id}', function (Request $request, Response $response, $args) use ($crud) {
    $log = new Logger();
    $log->log( "Elimina utente: " . $args['id'] );

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


  // delete
  $groupUsers->delete('/ruolo/{idutente}/{idruolo}', function (Request $request, Response $response, $args) use ($crud) {
    $Utils = new Utils();
    $log = new Logger();
    $db = new dataBase();

    $log->log( "Elimina ruolo: " . $args['idruolo'] );

    $arSelect = $Utils->dbSelect( [
      "log" => true,
      "delete" => true,
      "from" => "ssm_utenti_ruoli_lista",
      "where" => [
        [
        "field" => "idutente",
        "operator" => "=",
        "value" => $args['idutente'],
        "operatorAfter" => " AND "
        ],
        [
        "field" => "id",
        "operator" => "=",
        "value" => $args['idruolo'],
        ]
      ]
    ]);

    $log->log( "arSelect - " . json_encode( $arSelect ) );


    $sql = sprintf( "DELETE FROM ssm_utenti_settori_scientifici WHERE idutente='%s'", $db->real_escape_string( $p['id'] ) );
    $db->query( $sql );



    if( $arSelect['success'] == 1 ) {
      return $response
        ->withStatus(200);
    } else {
        $response->getBody()->write( "Errore aggiornamento" );
        return $response
          ->withStatus(400)
          ->withHeader('Content-Type', 'text/plain');
    }
  });

  $groupUsers->post('/v2/login/cas/{token}', function (Request $request, Response $response, $args) use ($auth) {
    $db = new dataBase();
    $log = new Logger();

    $p = json_decode($request->getBody(), true);
    $log->log( "LOGIN CAS2: " . json_encode( $p ) );

    if( $p['idruolo'] == "" ) {
      $a = new CASLogin();
      $res = $a->validateServiceTicket($args['token']);
      if( !$res['success'] ) {
        $response->getBody()->write("Login non riuscito, riprova.");
        return $response
          ->withStatus(400)
          ->withHeader('Content-Type', 'text/plain');
      }
    } else {
      $ret = verifyToken( $args['token'], $auth );
      if ($ret['success']) {
        $log->log( "SUCCESS CAS");
        $res = $ret;
      } else {
        $response = new GuzzleHttp\Psr7\Response();
        $response->getBody()->write($ret['errorDescription']);
        return $response
          ->withStatus(401);
      }
    }

    $log->log( "TOKEN CAS: " . json_encode( $res ) );

    $sql = sprintf("SELECT id,cognome, nome, email, anno_scuola
      FROM ssm_utenti
      WHERE codice_fiscale='%s'", $db->real_escape_string( $res['user']['fiscalCode'] ) );
    $db->query($sql);
    $rec = $db->fetchassoc();

    $log->log( "LOGIN CAS V2: " . json_encode( $rec ) );

    $recUtente = utente_data_get( $rec['id'], $p['idruolo'] );
    $log->log( "utente_data_get - " . json_encode( $recUtente ) );

    if( $recUtente[0]['idruolo'] == $recUtente[1]['idruolo'] ) {
      $recUtente = $recUtente[0];
    } elseif( sizeof( $recUtente ) > 1 ) {
      $rec['fiscalCode'] = $res['user']['fiscalCode'];
      $roles['token'] = $auth->tokenRefresh(1, $rec );
      $roles['roles'] = utente_roles_list( $rec['id'] );
      $response->getBody()->write( json_encode( $roles ) );
      return $response
        ->withStatus(200)
        ->withHeader('Content-Type', 'application/json');
    } else {
      $recUtente = $recUtente[0];
    }

    $rec['idscuola'] = $recUtente['idscuola'];
    $rec['idruolo'] = $recUtente['idruolo'];
    $log->log( "Dati utente - " . json_encode( $rec ) );

    $jwt = $auth->tokenRefresh(1, $rec);
    $rec['token'] = $jwt;

    foreach ($recUtente as $k => $v) {
        $rec[$k] = $v;
    }

    $response->getBody()->write(json_encode($rec));
      return $response
        ->withStatus(200)
        ->withHeader('Content-Type', 'application/json');

  });
  $groupUsers->get('/calcoloanno/{idspecializzando}', function (Request $request, Response $response, $args) {
    $res = annoCalc($args['idspecializzando']);
    $response->getBody()->write(json_encode($res));
      return $response
        ->withStatus(200)
        ->withHeader('Content-Type', 'application/json');
  });
});

function annoCalc($idSpecializzando)  {
  $specializzando = utente_get($idSpecializzando);
  $dataContratto = new \DateTime($specializzando['user']['data_contratto']);
  $dataCambio = $dataContratto->add(new \DateInterval("P1Y"));
  echo $specializzando['user']['nome'] . " " . $specializzando['user']['cognome'] . "<br><br>";
  echo "Data contratto: " . $specializzando['user']['data_contratto'] . " Anno: " . $specializzando['user']['anno_scuola'] . "<br><br>";
  echo "Passerebbe di anno il: " . $dataCambio->format('Y-m-d') . "<br><br>";
  $giorniSospensive = calcGiorniSospensive($specializzando['sospensive']);
  $dataCambioEffettiva = $dataCambio->modify("+$giorniSospensive day");
  $dataValutazione = dataValutazione($idSpecializzando, $specializzando['user']['anno_scuola']);
  if ($dataValutazione == false)  {
    echo "Salta";
    exit;
  } else {
    echo "Valutazione in data " . $dataValutazione . "<br>";
    $dataValutazione = new \DateTime($dataValutazione);
  }
  echo $giorniSospensive . " sospensive.<br>";
  $today = new \DateTime();
  if ($today >= $dataCambioEffettiva)  {
    echo "Cambia anno <br>";
    if ($dataValutazione > $dataCambioEffettiva) {
      echo "Prendo la data della valutazione come data di cambio anno";
      $dataCambioEffettiva = $dataValutazione;
    }
    $dataCambioEffettiva = $dataCambioEffettiva->format("Y-m-d");
    echo "<br>" . $dataCambioEffettiva;
    cambiaAnno($idSpecializzando, $dataCambioEffettiva, $specializzando['user']['anno_scuola'] + 1);
  } else {
    echo "Ancora no";
  }
}

function cambiaAnno($idSpecializzando, $data, $anno)  {
  $utils = new Utils();
  $ar = array(
    "idutente" => $idSpecializzando,
    "data_avanzamento" => $data,
    "anno" => $anno
  );
  $res = $utils->dbSql(true, "ssm_utenti_carriera", $ar);
  if (!$res['success']) {
    echo "Non sono riuscito a salvare nella tabella della carriera";
    exit;
  }
  $ar = array(
    "anno_scuola" => $anno
  );
  $res = $utils->dbSql(false, "ssm_utenti", $ar, "id", $idSpecializzando);
  if (!$res['success']) {
    echo "Non sono riuscito a salvare il nuovo anno nella tabella degli utenti";
    exit;
  }
}

function calcGiorniSospensive($sospensive)  {
  $countAll = 0;
  foreach ($sospensive as $s) {
    $from = new \DateTime($s['data_inizio']);
    $to = new \DateTime($s['data_fine']);
    $count = $from->diff($to, true)->format("%a");
    echo "Sospensiva dal " . $s['data_inizio']. "<br><br>";
    echo "Sospensiva al " . $s['data_fine']. "<br><br>";
    echo "Quindi giorni: " . $count . "<br><br>";
    $countAll += $count;
  }
  return $countAll;
}

function dataValutazione($idSpecializzando, $anno) {
  $utils = new Utils();
  $arSql = array(
    "select" => ["date_create"],
    "from" => "ssm_valutazioni_tutor",
    "where" => [
      [
        "field" => "idspecializzando",
        "value" => $idSpecializzando,
        "operatorAfter" => "AND"
      ],
      [
        "field" => "anno",
        "value" => $anno
      ]
    ],
    "limit" => [0, 1]
  );

  $arrSql = $utils->dbSelect($arSql);
  return isset($arrSql['data'][0]['date_create']) ? $arrSql['data'][0]['date_create'] : false;
}

function utente_ruoli_lista_get( $idutente ) {
  $Utils = new Utils();
    $log = new Logger();
    $db = new dataBase();

    $arSelect = $Utils->dbSelect( [
      "log" => true,
      "select" => ["ssm_utenti_ruoli_lista.id", "ru.nome", "ate.nome_ateneo", "scu.nome_scuola", "CONCAT(pr.nome, ' - ', a.nome) as presidio_nome", "uo.nome as unita_nome"],
      "from" => "ssm_utenti_ruoli_lista",
      "where" => [
        [
        "field" => "idutente",
        "operator" => "=",
        "value" => $idutente,
        ]
      ],
      "join" => [
        [
          "ssm.ssm_utenti_ruoli ru",
          "ru.id=ssm_utenti_ruoli_lista.idruolo"
        ],
        [
          "ssm.ssm_atenei ate",
          "ate.id=ssm_utenti_ruoli_lista.idateneo"
        ],
        [
          "ssm.ssm_scuole_atenei sc",
          "sc.id=ssm_utenti_ruoli_lista.idscuola"
        ],
        [
          "ssm.ssm_scuole scu",
          "scu.id=sc.idscuola"
        ],
        [
          "ssm.ssm_presidi pr",
          "pr.id=ssm_utenti_ruoli_lista.idpresidio"
        ],
        [
          "ssm.ssm_aziende a",
          "a.id=pr.idazienda"
        ],
        [
          "ssm.ssm_unita_operative uo",
          "uo.id=ssm_utenti_ruoli_lista.idunita"
        ]
      ]
    ]);

    foreach( $arSelect['data'] as $k => $v ) {
      $t = $v['nome_ateneo'];
      if( $v['nome_scuola'] != "" )
        $t .= " - " . $v['nome_scuola'];
      if( $v['presidio_nome'] != "" )
        $t .= " - " . $v['presidio_nome'];
      if( $v['unita_nome'] != "" )
        $t .= " - " . $v['unita_nome'];

      $ar[] = array( "id" => $v['id'],
        "nome" => $v['nome'],
        "testo" => $t
      );
    }

    return $ar;
}

function utente_ruoli_set( $idutente, $ruolo ) {
  $Utils = new Utils();
  $log = new Logger();

  $log->log( "utente_ruoli_set - " . json_encode( $ruolo ) );

  /*
  $arSelect = $Utils->dbSelect( [
    "log" => true,
    "delete" => true,
    "from" => "ssm_utenti_ruoli_lista",
    "where" => [
      [
      "field" => "idutente",
      "operator" => "=",
      "value" => $idutente,
      ]
    ],
  ]);
  */


  $ruolo['idutente'] = $idutente;
  $ruolo['idstatus'] = 1;
  $ruolo['date_create'] = "now()";
  $ruolo['date_update'] = "now()";
  $Utils->dbSql( true, "ssm_utenti_ruoli_lista", $ruolo, "", "" );

}

function utente_roles_list($idutente)
{
    $db = new dataBase();
    $log = new Logger();

    $sql = sprintf("SELECT DISTINCT url.idruolo as id, r.nome as text
    FROM ssm_utenti_ruoli_lista url
    LEFT JOIN ssm.ssm_utenti_ruoli r ON r.id=url.idruolo
    WHERE url.idutente='%s'", $db->real_escape_string($idutente));
    $db->query($sql);
    $log->log($sql);
    while ($rec = $db->fetchassoc()) {
        $ar[] = $rec;
    }

    return $ar;
}

function utente_data_get( $idutente, $idruolo = "" ) {

  $Utils = new Utils();
  $arSql = [
    "log" => true,
    "select" => [
        "u.nome", "u.cognome", "u.matricola",
        "ssm_utenti_ruoli_lista.idscuola", "ssm_utenti_ruoli_lista.idateneo", "idruolo", "s.nome_scuola",
        "a.nome_ateneo", "ru.nome as nome_ruolo", "u.anno_scuola",
        "pr.nome as presidio_nome", "uo.nome as unita_nome"
      ],
    "from" => "ssm_utenti_ruoli_lista",
    "join" => [
      [
      "ssm.ssm_scuole_atenei sa",
      "sa.id=ssm_utenti_ruoli_lista.idscuola"
      ],
      [
      "ssm.ssm_atenei a",
      "a.id=ssm_utenti_ruoli_lista.idateneo"
      ],
      [
      "ssm.ssm_scuole s",
      "s.id=sa.idscuola"
      ],
      [
      "ssm_utenti u",
      "u.id=ssm_utenti_ruoli_lista.idutente"
      ],
      [
      "ssm.ssm_utenti_ruoli ru",
      "ru.id=ssm_utenti_ruoli_lista.idruolo"
      ],
      [
      "ssm.ssm_presidi pr",
      "pr.id=ssm_utenti_ruoli_lista.idpresidio"
      ],
      [
      "ssm.ssm_unita_operative uo",
      "uo.id=ssm_utenti_ruoli_lista.idunita"
      ],
    ],
    "where" => [
      [
      "field" => "idutente",
      "value" => $idutente,
      ]
    ]
  ];

  if ($idruolo && $idruolo != "") {
    $arSql["where"][0]["operatorAfter"] = "AND";
    $arSql["where"][] = array(
      "field" => "ssm_utenti_ruoli_lista.idruolo",
      "value" => $idruolo
    );
  }

  $utente_scuola = $Utils->dbSelect($arSql)['data'];

  return $utente_scuola;
}

function utente_data_get_cf( $codiceFiscale, $idruolo = "" ) {
  $Utils = new Utils();
  $arSql = [
    "log" => true,
    "select" => [
        "u.id", "u.nome", "u.cognome", "u.matricola", "u.email",
        "ssm_utenti_ruoli_lista.idscuola", "ssm_utenti_ruoli_lista.idateneo", "idruolo", "s.nome_scuola",
        "a.nome_ateneo", "ru.nome as nome_ruolo", "u.anno_scuola",
        "pr.nome as presidio_nome", "uo.nome as unita_nome"
      ],
    "from" => "ssm_utenti_ruoli_lista",
    "join" => [
      [
      "ssm.ssm_scuole_atenei sa",
      "sa.id=ssm_utenti_ruoli_lista.idscuola"
      ],
      [
      "ssm.ssm_atenei a",
      "a.id=ssm_utenti_ruoli_lista.idateneo"
      ],
      [
      "ssm.ssm_scuole s",
      "s.id=sa.idscuola"
      ],
      [
      "ssm_utenti u",
      "u.id=ssm_utenti_ruoli_lista.idutente"
      ],
      [
      "ssm.ssm_utenti_ruoli ru",
      "ru.id=ssm_utenti_ruoli_lista.idruolo"
      ],
      [
      "ssm.ssm_presidi pr",
      "pr.id=ssm_utenti_ruoli_lista.idpresidio"
      ],
      [
      "ssm.ssm_unita_operative uo",
      "uo.id=ssm_utenti_ruoli_lista.idunita"
      ],
    ],
    "where" => [
      [
      "field" => "codice_fiscale",
      "value" => $codiceFiscale,
      ]
    ]
  ];

  if ($idruolo != "") {
    $arSql["where"][0]["operatorAfter"] = "AND";
    $arSql["where"][] = array(
      "field" => "ssm_utenti_ruoli_lista.idruolo",
      "value" => $idruolo
    );
  }

  $utente_scuola = $Utils->dbSelect($arSql)['data'];

  return $utente_scuola[0];
}


function _utente_get_by_ruolo( $idscuola, $idruolo ) {
  $db = new dataBase();
  $log = new Logger();

  $sql = sprintf( "SELECT DISTINCT ut.id, concat(cognome,' ',nome) as text
    FROM ssm_utenti_ruoli_lista url
    LEFT JOIN ssm_utenti ut ON ut.id=url.idutente
    WHERE idscuola='%s' AND idruolo=%d AND ut.idstatus = 1 AND url.idstatus = 1
    ORDER BY cognome, nome",
    $db->real_escape_string( $idscuola ), $idruolo );
  $db->query( $sql );

  $ar = [];
  $log->log( "*** _utenti_get_by_ruolo - " . $sql );
  while( $rec = $db->fetchassoc() ) {
    $ar[] = $rec;
  }

  return $ar;
}



/* seleziona i tutor dell'unità operativa */
function _tutor_list( $arunita ) {
  $db = new dataBase();
  $log = new Logger();

  foreach( $arunita as $k => $v ) {
    $q[] = $db->real_escape_string($v);
  }

  $log->log( "_tutor_list - " . json_encode( $q ) );

  $sql = sprintf( "SELECT DISTINCT ut.id, concat(cognome,' ',nome) as text
    FROM ssm_utenti_ruoli_lista url
    LEFT JOIN ssm_utenti ut ON ut.id=url.idutente
    WHERE idunita IN ('%s') AND idruolo='%d' AND url.idstatus=1 AND ut.idstatus=1
    ORDER BY cognome, nome", implode( "', '", $q ), 7 );
  $log->log( "tutor_list - " . $sql );
  $db->query( $sql );

  $ar = [];
  $log->log( "*** _tutor_list - " . $sql );
  while( $rec = $db->fetchassoc() ) {
    $ar[] = $rec;
  }

  return $ar;
}


function scuola_anni_get( $idscuola ) {
  $db = new dataBase();
  $log = new Logger();

  $sql = sprintf( "SELECT numero_anni
    FROM ssm.ssm_scuole s
    LEFT JOIN ssm.ssm_scuole_atenei sa ON sa.idscuola=s.id
    WHERE sa.id='%s'", $db->real_escape_string( $idscuola ) );
  $db->query( $sql );
  $log->log( "scuola_anni_get - " . $sql );
  $rec = $db->fetchassoc();
  return $rec['numero_anni'];
}

function utente_get($id)
{
    $Utils = new Utils();
    $utente_scuola = $Utils->dbSelect([
      "select" => [
          "*"
        ],
      "from" => "ssm_utenti",
      "where" => [
        [
        "field" => "id",
        "value" => $id,
        ],
      ]
    ])['data'][0];
    $avanzamento_carriera = $Utils->dbSelect([
      "select" => [
          "data_avanzamento"
        ],
      "from" => "ssm_utenti_carriera",
      "where" => [
        [
        "field" => "idutente",
        "value" => $id,
        ],
      ],
      "order" => "data_avanzamento desc",
      "limit" => [0, 1]
    ])['data'][0];
    $sospensive = $Utils->dbSelect([
      "select" => [
          "*"
        ],
      "from" => "ssm_utenti_sospensive",
      "where" => [
        [
        "field" => "idutente",
        "value" => $id,
        "operatorAfter" => "AND"
        ],
        [
        "field" => "idstatus",
        "value" => 1,
        "operatorAfter" => "AND"
        ],
        [
        "field" => "anno",
        "value" => $utente_scuola['anno_scuola'],
        ],
      ]
    ])['data'];

    if ($avanzamento_carriera['data_avanzamento'] != '')  {
      $utente_scuola['data_contratto'] = $avanzamento_carriera['data_avanzamento'];
    }
    return array(
      "user" => $utente_scuola,
      "sospensive" => $sospensive
    );
}

?>

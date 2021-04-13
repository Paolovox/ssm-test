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




$app->group('/jobtabelle', function (RouteCollectorProxy $groupJobTabelle) use ($auth) {

  $data = [
    "table" => "ssm_jobdescription_tabelle",
    "id" => "id",
    "sort" => "norder",
    "order" => "asc",
    "status_field" => "idstatus",
    "update_field" => "date_update",
    "create_field" => "date_create",
    "list_fields" => [ "id", "nome_tabella", "date_format( date_update, '%d-%m-%Y') as data_aggiornamento_text", "norder" ],
  ];


  $dataColonne = [
    "table" => "ssm_jobdescription_colonne",
    "id" => "id",
    "sort" => "norder",
    "order" => "asc",
    "status_field" => "idstatus",
    "update_field" => "date_update",
    "create_field" => "date_create",
    "list_fields" => [ "id", "nome_colonna", "date_format( date_update, '%d-%m-%Y') as data_aggiornamento_text", "norder" ],
  ];

  $crud = new CRUD( $data );
  $crudColonne = new CRUD( $dataColonne );

  // list
  $groupJobTabelle->get('', function (Request $request, Response $response) use ($auth, $crud) {
      $db = new dataBase();
      $log = new Logger();

      $user = $request->getAttribute('user');
      $p = $request->getQueryParams();

      $p['_ssm_jobdescription_tabelle.idstatus']=1;
      $p['_ssm_jobdescription_tabelle.idscuola'] = $user['idscuola'];


      if( $p['s'] != "" ) {
        $p['search'] = array(
          "field" => "ssm_jobdescription_tabelle.nome_tabella",
          "operator" => " LIKE ",
          "value" => "%" . $p['s'] . "%"
        );
      }

      $res = $crud->record_list( $p );
      if( !$res )
        $res = [];

      for( $n=0; $n<sizeof($res['rows']); $n++ ) {
        $res['rows'][$n]['canEdit'] = false;
        if( $user['idruolo'] == USER_SEGRETERIA ) {
          $res['rows'][$n]['canEdit'] = true;
        }
      }

      $res['canAdd'] = false;
      if( $user['idruolo'] == USER_SEGRETERIA ) {
        $res['canAdd'] = true;
      }

      $response->getBody()->write( json_encode( $res, JSON_NUMERIC_CHECK ) );
      return $response;
  });

  // get
  $groupJobTabelle->get('/{id}', function (Request $request, Response $response, $args) use ($crud) {
      $Utils = new Utils();
      $log = new Logger();

      $user = $request->getAttribute('user');
      $res = $crud->record_get( $args['id'] )['data'][0];
      if( !$res )
        $res = [];

      $response->getBody()->write( json_encode( $res ) );
      return $response;
  });

  // new
  $groupJobTabelle->put('', function (Request $request, Response $response) use ($crud ) {

    $log = new Logger();

    $p = json_decode($request->getBody(), true);
    $user = $request->getAttribute('user');

    $p['idscuola'] = $user['idscuola'];
    $p['norder'] = _next_order( "ssm_jobdescription_tabelle", $user['idscuola'] );

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
  $groupJobTabelle->post('/{id}', function (Request $request, Response $response, $args) use ($crud) {
    $log = new Logger();

    $p = json_decode($request->getBody(), true);

    $retValidate = validate( "job_tabelle", $p );
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
  $groupJobTabelle->delete('/{id}', function (Request $request, Response $response, $args) use ($crud) {
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



  // list
  $groupJobTabelle->get('/colonne/{idtabella}', function (Request $request, Response $response, $args) use ($auth, $crudColonne) {
      $db = new dataBase();
      $log = new Logger();

      $log->log( "Colonne..." . $args['idtabella'] );

      $user = $request->getAttribute('user');
      $p = $request->getQueryParams();

      $p['_ssm_jobdescription_colonne.idstatus'] = 1;
      $p['_ssm_jobdescription_colonne.idtabella'] = $args['idtabella'];

      if( $p['s'] != "" ) {
        $q[] = sprintf( "ssm_jobdescription_tabelle.nome_colonna LIKE '%%%s%%'", $db->real_escape_string( $p['s'] ) );
      }

      $res = $crudColonne->record_list( $p );
      if( !$res )
        $res = [];

      $response->getBody()->write( json_encode( $res, JSON_NUMERIC_CHECK ) );
      return $response;
  });


  // get
  $groupJobTabelle->get('/colonne/{idtabella}/{id}', function (Request $request, Response $response, $args) use ($crudColonne) {
    $Utils = new Utils();
    $log = new Logger();

    $user = $request->getAttribute('user');
    $res = $crudColonne->record_get( $args['id'] )['data'][0];
    if( !$res )
      $res = [];

    $response->getBody()->write( json_encode( $res ) );
    return $response;
  });


  // new
  $groupJobTabelle->put('/colonne/{idtabella}', function (Request $request, Response $response, $args) use ($crudColonne ) {
    $log = new Logger();

    $p = json_decode($request->getBody(), true);
    $user = $request->getAttribute('user');

    $p['idscuola'] = $user['idscuola'];
    $p['idtabella'] = $args['idtabella'];
    $p['norder'] = _next_order( "ssm_jobdescription_colonne", $args['idtabella'] );

    $res = $crudColonne->record_new( $p );
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
  $groupJobTabelle->post('/colonne/{idtabella}/{id}', function (Request $request, Response $response, $args) use ($crudColonne) {
    $log = new Logger();

    $p = json_decode($request->getBody(), true);

    $retValidate = validate( "job_colonne", $p );
    if( $retValidate != "" ) {
      $response->getBody()->write( $retValidate );
      return $response
        ->withStatus(400)
        ->withHeader('Content-Type', 'text/plain');
    }

    $res = $crudColonne->record_update( $args['id'], $p );
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
  $groupJobTabelle->delete('/colonne/{idtabella}/{id}', function (Request $request, Response $response, $args) use ($crudColonne) {
    $res = $crudColonne->record_delete( $args['id'] );
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




    // modifica ordine
    $groupJobTabelle->put('/order', function (Request $request, Response $response, $args) use ($crud) {
      $log = new Logger();
      $db = new dataBase();

      $user = $request->getAttribute('user');
      $log->log( "user - " . json_encode( $user ) );

      $p = json_decode($request->getBody(), true);
      $log->log( "order - " . json_encode( $p ) );

      // prende gli id dei piatti coinvolti
      $sql = sprintf( "SELECT id,norder FROM ssm_jobdescription_tabelle WHERE id='%s' OR id='%s'", $p['cur_id'], $p['des_id'] );
      $log->log( "seleziona - " . $sql );
      $db->query( $sql );
      while( $rec = $db->fetchassoc() ) {
        if( $rec['id'] == $p['cur_id'] )
          $p['cur_pos'] = $rec['norder'];
        if( $rec['id'] == $p['des_id'] )
          $p['des_pos'] = $rec['norder'];
        $log->log( "POSIZIONI: " . json_encode( $p ) );
      }

      $log->log( $p['cur_pos'] . '->' . $p['des_pos'] );
      $move = $p['cur_pos'] > $p['des_pos'] ? 'up' : 'down';
      $sql = sprintf( "UPDATE ssm_jobdescription_tabelle SET norder=%d WHERE idscuola='%s' AND id='%s'", $p['des_pos'], $user['idscuola'], $p['cur_id'] );
      $log->log( $sql );
      $db->query( $sql );

      if( $p['cur_pos'] > $p['des_pos'] ) {
        $sql = sprintf( "UPDATE ssm_jobdescription_tabelle
          SET norder=norder+1
          WHERE idscuola='%s'
            AND norder>=%d AND norder<%d and id!='%s' ORDER BY norder", $user['idscuola'], $p['des_pos'], $p['cur_pos'], $p['cur_id'] );
        $log->log( $sql );
        $db->query( $sql );
      }

      if( $p['cur_pos'] < $p['des_pos'] ) {
        $sql = sprintf( "UPDATE ssm_jobdescription_tabelle
          SET norder=norder-1
          WHERE idscuola='%s'
            AND norder<=%d AND norder>%d and id!='%s' ORDER BY norder", $user['idscuola'], $p['des_pos'], $p['cur_pos'], $p['cur_id'] );
        $log->log( $sql );
        $db->query( $sql );
      }

      return $response
        ->withStatus(200)
        ->withHeader('Content-Type', 'application/json');

    });



    // modifica ordine
    $groupJobTabelle->put('/colonne/order/{idtabella}', function (Request $request, Response $response, $args) use ($crud) {
      $log = new Logger();
      $db = new dataBase();

      $user = $request->getAttribute('user');
      $log->log( "user - " . json_encode( $user ) );

      $p = json_decode($request->getBody(), true);
      $log->log( "order - " . json_encode( $p ) );

      // prende gli id dei piatti coinvolti
      $sql = sprintf( "SELECT id,norder FROM ssm_jobdescription_colonne WHERE id='%s' OR id='%s'", $p['cur_id'], $p['des_id'] );
      $log->log( "seleziona - " . $sql );
      $db->query( $sql );
      while( $rec = $db->fetchassoc() ) {
        if( $rec['id'] == $p['cur_id'] )
          $p['cur_pos'] = $rec['norder'];
        if( $rec['id'] == $p['des_id'] )
          $p['des_pos'] = $rec['norder'];
        $log->log( "POSIZIONI: " . json_encode( $p ) );
      }

      $log->log( $p['cur_pos'] . '->' . $p['des_pos'] );
      $move = $p['cur_pos'] > $p['des_pos'] ? 'up' : 'down';
      $sql = sprintf( "UPDATE ssm_jobdescription_colonne SET norder=%d WHERE idtabella='%s' AND id='%s'", $p['des_pos'], $args['idtabella'], $p['cur_id'] );
      $log->log( $sql );
      $db->query( $sql );

      if( $p['cur_pos'] > $p['des_pos'] ) {
        $sql = sprintf( "UPDATE ssm_jobdescription_colonne
          SET norder=norder+1
          WHERE idtabella='%s'
            AND norder>=%d AND norder<%d and id!='%s' ORDER BY norder", $args['idtabella'], $p['des_pos'], $p['cur_pos'], $p['cur_id'] );
        $log->log( $sql );
        $db->query( $sql );
      }

      if( $p['cur_pos'] < $p['des_pos'] ) {
        $sql = sprintf( "UPDATE ssm_jobdescription_colonne
          SET norder=norder-1
          WHERE idtabella='%s'
            AND norder<=%d AND norder>%d and id!='%s' ORDER BY norder", $args['idtabella'], $p['des_pos'], $p['cur_pos'], $p['cur_id'] );
        $log->log( $sql );
        $db->query( $sql );
      }

      return $response
        ->withStatus(200)
        ->withHeader('Content-Type', 'application/json');

    });



    // dati list
    $groupJobTabelle->get('/dati/{idtabella}', function (Request $request, Response $response, $args) use ($auth, $crudColonne) {
      $db = new dataBase();
      $log = new Logger();

      $user = $request->getAttribute('user');
      $p = $request->getQueryParams();

      // seleziona le colonne
      $sql = sprintf( "SELECT jc.id, nome_colonna
        FROM ssm_jobdescription_colonne jc
        LEFT JOIN ssm_jobdescription_tabelle jt ON jt.id = jc.idtabella
        WHERE jc.idtabella = '%s'
          AND jc.idstatus = 1
          AND jt.idstatus =1
        ORDER BY jc.norder", $db->real_escape_string( $args['idtabella'] ) );
      $db->query( $sql );
      while( $rec = $db->fetchassoc() ) {
        $arColonne[$rec['id']] = $rec['nome_colonna'];
        $res['colonne'][] = array(
          "column" => $rec['id'],
          "name" => $rec['nome_colonna'],
          "headerDisabled" => true
        );
      }

      //$log->log( "Colonne: " . $sql . " - " . json_encode( $arColonne ) );


      if( $p['s'] != "" ) {
        $where = sprintf( " AND dati_colonne like '%%%s%%'", $p['s'] );
      }


      $sql = sprintf( "SELECT SQL_CALC_FOUND_ROWS jd.*
        FROM ssm_jobdescription_dati jd
        LEFT JOIN ssm_jobdescription_tabelle jt ON jt.id=jd.idtabella
        WHERE idtabella='%s'
          AND jd.idstatus=1
          AND jt.idstatus=1
          %s
          ORDER BY jd.norder
          LIMIT %d,%d",
          $db->real_escape_string( $args['idtabella'] ),
          $where,
          ($p['p']-1) * $p['c'], $p['c'] );
      $db->query( $sql );
      //$log->log( "Colonne: " . $sql );

      while( $rec = $db->fetchassoc() ) {
        $dati_colonne = json_decode( $rec['dati_colonne'], true );
        //$log->log( "dati_colonne - " . json_encode( $dati_colonne ) );
        foreach( $dati_colonne as $k => $v ) {
          $rec[$arColonne[$v['idcolonna']]] = $v['testo'];
        }

        $rec['canEdit']['canEdit'] = false;
        if( $user['idruolo'] == USER_SEGRETERIA ) {
          $rec['canEdit'] = true;
        }

        $res['rows'][] = $rec;
      }

      if( !$res )
        $res = [];


      $db->query( "SELECT FOUND_ROWS()" );
      $resCount = $db->fetcharray();
      $res['total'] = $resCount[0];
      $res['count'] = sizeof( $ar );


      $res['canAdd'] = false;
      if( $user['idruolo'] == USER_SEGRETERIA ) {
        $res['canAdd'] = true;
      }

      $response->getBody()->write( json_encode( $res, JSON_NUMERIC_CHECK ) );
      return $response;
  });



  // get
  $groupJobTabelle->get('/dati/{idtabella}/{id}', function (Request $request, Response $response, $args) use ($crudColonne) {
    $Utils = new Utils();
    $log = new Logger();
    $db = new dataBase();

    $sql = sprintf( "SELECT id, nome_colonna
      FROM ssm_jobdescription_colonne
      WHERE idtabella='%s' AND idstatus=1
      ORDER BY norder", $db->real_escape_string( $args['idtabella'] ) );
    //$log->log( "GET TABELLA: " . $sql );
    $db->query( $sql );
    $ar = array();
    while( $rec = $db->fetchassoc() ) {
      $res['dialogFields'][] = array(
        "type" => "INPUT",
        "placeholder" => $rec['nome_colonna'],
        "name" => $rec['id']
      );
    }


    $sql = sprintf( "SELECT dati_colonne
      FROM ssm_jobdescription_dati jd
      WHERE id='%s'", $db->real_escape_string( $args['id'] ) );
    $db->query( $sql );
    $rec = $db->fetchassoc();
    $data = json_decode( $rec['dati_colonne'], true );
    $log->log( "DATI: " . $sql . " - " . json_encode( $rec ) );
    foreach( $data as $k => $v ) {
      $res['data'][$v['idcolonna']] = $v['testo'];
    }


    $response->getBody()->write( json_encode( $res ) );
    return $response;
  });


  // dati new
  $groupJobTabelle->put('/dati/{idtabella}', function (Request $request, Response $response, $args) {
    $log = new Logger();
    $UUID = new UUID();
    $Utils = new Utils();

    $p = json_decode($request->getBody(), true);
    $user = $request->getAttribute('user');

    $data['id'] = $UUID->v4();
    $data['idscuola'] = $user['idscuola'];
    $data['idtabella'] = $args['idtabella'];
    $data['norder'] = _next_order( "ssm_jobdescription_dati", $args['idtabella'] );
    $data['idstatus'] = 1;
    $data['date_create'] = 'now()';
    $data['date_update'] = 'now()';

    foreach( $p as $k => $v ) {
      $ar[] = array(
        'idcolonna' => $k,
        'testo' => $v );
    }
    $data['dati_colonne'] = json_encode( $ar );


    $res = $Utils->dbSql( true, "ssm_jobdescription_dati", $data, "", "" );

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



  //update
  $groupJobTabelle->post('/dati/{idtabella}/{id}', function (Request $request, Response $response, $args) use ($crudColonne) {
    $log = new Logger();
    $Utils = new Utils();

    $p = json_decode($request->getBody(), true);
    $log->log( "modifica dati tabella: " . json_encode( $p ) );

    foreach( $p as $k => $v ) {
      $ar[] = array(
        'idcolonna' => $k,
        'testo' => $v );
    }
    $data['dati_colonne'] = json_encode( $ar );


    $res = $Utils->dbSql( false, "ssm_jobdescription_dati", $data, "id", $args['id'] );
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
  $groupJobTabelle->delete('/dati/{idtabella}/{id}', function (Request $request, Response $response, $args) use ($crudColonne) {
    $db = new dataBase();
    $sql = sprintf( "UPDATE ssm_jobdescription_dati SET idstatus=2, date_update=now() WHERE id='%s'", $db->real_escape_string( $args['id'] ) );
    $db->query( $sql );
    if( $db ) {
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




    // modifica ordine
    $groupJobTabelle->put('/dati/order/{idtabella}', function (Request $request, Response $response, $args) use ($crud) {
      $log = new Logger();
      $db = new dataBase();

      $user = $request->getAttribute('user');

      $p = json_decode($request->getBody(), true);

      $sql = sprintf( "SELECT id,norder FROM ssm_jobdescription_dati WHERE id='%s' OR id='%s'", $p['cur_id'], $p['des_id'] );
      $log->log( "seleziona - " . $sql );
      $db->query( $sql );
      while( $rec = $db->fetchassoc() ) {
        if( $rec['id'] == $p['cur_id'] )
          $p['cur_pos'] = $rec['norder'];
        if( $rec['id'] == $p['des_id'] )
          $p['des_pos'] = $rec['norder'];
      }

      $log->log( $p['cur_pos'] . '->' . $p['des_pos'] );
      $move = $p['cur_pos'] > $p['des_pos'] ? 'up' : 'down';
      $sql = sprintf( "UPDATE ssm_jobdescription_dati SET norder=%d WHERE idtabella='%s' AND id='%s'", $p['des_pos'], $args['idtabella'], $p['cur_id'] );
      $log->log( $sql );
      $db->query( $sql );

      if( $p['cur_pos'] > $p['des_pos'] ) {
        $sql = sprintf( "UPDATE ssm_jobdescription_dati
          SET norder=norder+1
          WHERE idtabella='%s'
            AND norder>=%d AND norder<%d and id!='%s' ORDER BY norder", $args['idtabella'], $p['des_pos'], $p['cur_pos'], $p['cur_id'] );
        $log->log( $sql );
        $db->query( $sql );
      }

      if( $p['cur_pos'] < $p['des_pos'] ) {
        $sql = sprintf( "UPDATE ssm_jobdescription_dati
          SET norder=norder-1
          WHERE idtabella='%s'
            AND norder<=%d AND norder>%d and id!='%s' ORDER BY norder", $args['idtabella'], $p['des_pos'], $p['cur_pos'], $p['cur_id'] );
        $log->log( $sql );
        $db->query( $sql );
      }

      return $response
        ->withStatus(200)
        ->withHeader('Content-Type', 'application/json');

    });



})->add($authMW);



function _next_order( $table, $idtabella ) {
  $db = new dataBase();
  $log = new Logger();
  $sql = sprintf( "SELECT MAX(norder) as norder_max FROM %s WHERE idtabella='%s' AND idstatus=1", $table, $idtabella );
  $log->log( $sql );

  $db->query( $sql );
  $rec = $db->fetchassoc();
  if( $rec['norder_max'] > 0 )
    return $rec['norder_max'] + 1;
  return 1;
}

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


$app->group('/registrazioni_filtri', function (RouteCollectorProxy $groupFiltro) use ($auth) {

  $data = [
    "log" => true,
    "table" => "ssm.ssm_registrazioni_filtri",
    "id" => "id",
    "sort" => "nome",
    "order" => "asc",
    "status_field" => "idstatus",
    "update_field" => "date_update",
    "create_field" => "date_create",
    "list_fields" => [ "ssm.ssm_registrazioni_filtri.id", "ra.nome as attivita_text", "ci.nome as prestazione_text", "ci.id as idprestazione", "ssm.ssm_registrazioni_filtri.combo" ],
    "list_join" => [
      [
          "ssm.ssm_registrazioni_attivita ra",
          " ra.id=ssm.ssm_registrazioni_filtri.idattivita "
      ],
      [
          "ssm.ssm_registrazioni_combo_items ci",
          " ci.id=ssm.ssm_registrazioni_filtri.idprestazione "
      ]
    ]
  ];

  $crud = new CRUD( $data );

  // list
  $groupFiltro->get('/{idscuola}/{idattivita}', function (Request $request, Response $response, $args) use ($auth, $crud) {
    $log = new Logger();
    $db = new dataBase();

    $p = $request->getQueryParams();

    if( $p['srt'] == "nome" )
      $p['srt'] = "ci.nome";

    if( $p['s'] != "" ) {
      $p['search'] = array(
        "field" => "ci.nome",
        "operator" => " LIKE ",
        "value" => "%" . $p['s'] . "%"
      );
    }

    $p['_ssm.ssm_registrazioni_filtri.idstatus'] = "1";
    $p['_ssm.ssm_registrazioni_filtri.idscuola'] = $args['idscuola'];
    $p['_ssm.ssm_registrazioni_filtri.idattivita'] = $args['idattivita'];

    $res = $crud->record_list( $p );

    // ripercorre tutta la selezione per rendere visibili
    // le descrizioni delle combo selezionate
    for( $n=0; $n<sizeof( $res['rows'] ); $n++ ) {
      $combos = json_decode( $res['rows'][$n]['combo'], 1 );
      unset( $idvalue );
      //$log->log( "Combo: " . json_encode( $combos ) );
      foreach( $combos as $v ) {
        $idvalue[] = $db->real_escape_string( $v['idvalue'] );
      }

      //$log->log( "idvalue " . json_encode( $idvalue ) );

      // TODO: SQL_INJECTION_TEST
      $sql = sprintf( "SELECT co.nome as co_nome,ci.nome as ci_nome
        FROM ssm.ssm_registrazioni_combo_items ci
        LEFT JOIN ssm.ssm_registrazioni_combo co ON co.id=ci.idcombo
        WHERE ci.id IN ('%s')", implode( "','", $idvalue ) );
      $db->query( $sql );
      $o = "";
      while( $rec = $db->fetchassoc() ) {
        if( $o != "" )
          $o .= ", ";
        $o .= $rec['co_nome'] . " -> " . $rec['ci_nome'];
      }
      //$log->log( "VALUES: " . $sql );
      //$log->log( "VALUES OUT: " . $o );

      $res['rows'][$n]['combo_text'] = $o;
    }

    //$log->log( "registrazioni_schema - list " . json_encode( $res ) );

    $response->getBody()->write( json_encode( $res ) );
    return $response;
  });

  // get
  $groupFiltro->get('/{idscuola}/{idattivita}/{id}', function (Request $request, Response $response, $args) use ($crud) {
      $Utils = new Utils();
      $log = new Logger();
      $db = new dataBase();

      $res = $crud->record_get( $args['id'] )['data'][0];
      if( $res == "" )
        $res = [];

      $log->log( "GET /attivita" . json_encode( $res ) );

      $combo = json_decode( $res['combo'], true );
      foreach( $combo as $k => $v ) {
        $res[$v['idcombo']] = $v['idvalue'];
      }

      /*
      $combo = json_decode( $res['combo_implicite'], true );
      foreach( $combo as $k => $v ) {
        $res[$v['idcombo']] = $v['idvalue'];
      }
      */

      unset( $res['combo'] );

      /*
      $where = sprintf( "idstatus=1 AND idscuola='%s'", $args['idscuola'] );
      $ar = array( "table" => "ssm.ssm_registrazioni_attivita", "value" => "id", "text" => "nome", "order" => "nome", "where" => $where );
      $res['attivita_list'] = $Utils->_combo_list( $ar, true, "" );
      */

      // lista prestazioni
      // $sql = sprintf( "SELECT ci.id, ci.nome as text
      //   FROM ssm.ssm_registrazioni_combo_items ci
      //   LEFT JOIN ssm.ssm_registrazioni_combo co ON co.id=ci.idcombo
      //   LEFT JOIN ssm.ssm_registrazioni_attivita sra ON JSON_CONTAINS(
      //       sra.prestazioni, %s )
      //   WHERE co.idscuola='%s' AND co.idtipo=1 AND co.idstatus=1 AND ci.idstatus=1
      //     AND JSON_CONTAINS( sra.prestazioni, %s )
      //   ORDER BY ci.nome",
      //     "concat('\"', ci.id, '\"')",
      //     $args['idscuola'],
      //     "concat('\"', ci.id, '\"')",
      //   );
      // $log->log( "PRESTAZIONI: " . $sql );
      // $db->query( $sql );
      // while( $rec = $db->fetchassoc() ) {
      //   $res['prestazioni_list'][] = $rec;
      // }

      $sql = sprintf("SELECT prestazioni FROM ssm.ssm_registrazioni_attivita WHERE id = '%s' AND idstatus=1", $db->real_escape_string( $args['idattivita'] ) );
      $db->query($sql);
      $rec = $db->fetchassoc();

      // TODO: SQL_INJECTION_TEST
      $prestazioni = json_decode($rec['prestazioni'], true);
      foreach ($prestazioni as $value) {
        $value = $db->real_escape_string( $value );
        $prestazioniIn .= "'$value',";
      }
      $prestazioniIn = substr($prestazioniIn, 0, -1);

      $sql = sprintf("SELECT
                        ci.id,
                        ci.nome AS text
                      FROM ssm.ssm_registrazioni_combo_items ci
                      WHERE id IN(%s) AND idstatus=1 ORDER BY ci.nome", $prestazioniIn );
      $db->query($sql);
      while ($rec = $db->fetchassoc()) {
          $res['prestazioni_list'][] = $rec;
      }


      $response->getBody()->write( json_encode( $res ) );
      return $response;
  });

  // new
  $groupFiltro->put('/{idscuola}/{idattivita}', function (Request $request, Response $response, $args) use ($crud ) {
    $log = new Logger();

    $p = json_decode($request->getBody(), true);
    $log->log( "/registrazioni_filtri/idscuola - " . json_encode( $p ) );


    $retValidate = validate( "registrazioni_filtri", $ar );
    if( $retValidate != "" ) {
      $response->getBody()->write( $retValidate );
      return $response
        ->withStatus(400)
        ->withHeader('Content-Type', 'text/plain');
    }

    $prestazioni_list = $p['idprestazione'];
    $idattivita = $p['idattivita'];
    unset( $p['idprestazione'] );
    unset( $p['idattivita'] );
    foreach( $p as $k => $v ) {
      $tipo = "E";
      if( substr( $k, 0, 4 ) == "imp_" )
        $tipo = "I";

      $ar['combo'][] = array( "idcombo" => $k, "idvalue" => $v, "tipo" => $tipo );
    }

    $ar['combo'] = json_encode( $ar['combo'] );
    $ar['idscuola'] = $args['idscuola'];
    $ar['idattivita'] = $args['idattivita'];

    foreach( $prestazioni_list as $prestazione ) {
      $ar["idprestazione"] = $prestazione;
      $res = $crud->record_new( $ar );
    }

    $log->log( "prestazioni new - " . json_encode( $ar ) );


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
  $groupFiltro->post('/{idscuola}/{idattivita}/{id}', function (Request $request, Response $response, $args) use ($crud) {
    $log = new Logger();

    $p = json_decode($request->getBody(), true);
    $log->log( "update - " . json_encode( $p ) );

    $ar = array(
      "idscuola" => $args['idscuola'],
      "idprestazione" => $p['idprestazione'],
      "idattivita" => $args['idattivita']
    );

    unset( $p['idprestazione'] );
    unset( $p['idattivita'] );

    foreach( $p as $k => $v ) {
      $tipo = "E";
      if( substr( $k, 0, 4 ) == "imp_" )
        $tipo = "I";

      $ar['combo'][] = array( "idcombo" => $k, "idvalue" => $v, "tipo" => $tipo );
    }
    $ar['combo'] = json_encode( $ar['combo'] );


    $retValidate = validate( "registrazioni_filtri", $ar );
    if( $retValidate != "" ) {
      $response->getBody()->write( $retValidate );
      return $response
        ->withStatus(400)
        ->withHeader('Content-Type', 'text/plain');
    }

    $res = $crud->record_update( $args['id'], $ar );
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
  $groupFiltro->delete('/{idscuola}/{idattivita}/{id}', function (Request $request, Response $response, $args) use ($crud) {
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


});



?>

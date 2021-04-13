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



$app->group('/atenei', function (RouteCollectorProxy $group) use ($auth) {

  $data = [
    "table" => "ssm.ssm_atenei",
    "id" => "id",
    "sort" => "nome_ateneo",
    "order" => "asc",
    "status_field" => "idstatus",
    "update_field" => "date_update",
    "create_field" => "date_create"
  ];
  $crud = new CRUD( $data );


  // list
  $group->get('', function (Request $request, Response $response) use ($auth, $crud) {
      $user = $request->getAttribute('user');
      $p = $request->getQueryParams();
      $p['_idstatus'] = "1";

      if( $p['s'] != "" ) {
        $p['search'] = array(
          "field" => "ssm.ssm_atenei.nome_ateneo",
          "operator" => " LIKE ",
          "value" => "%" . $p['s'] . "%"
        );
      }

      if($user['idruolo'] != 1 && $user['idruolo'] != 2 && $user['idruolo'] != 9)  {
        return $response->withStatus(401);
      }

      if($user['idruolo'] == 2 || $user['idruolo'] == 9)  {
        $p['_id'] = $user['idateneo'];
      }

      $res = $crud->record_list( $p );
      $response->getBody()->write( json_encode( $res ) );
      return $response;
  });

  // get
  $group->get('/{id}', function (Request $request, Response $response, $args) use ($crud) {
      $Utils = new Utils();
      $res = $crud->record_get( $args['id'] )['data'][0];

      // option selected sds
      $res['id_sds'] = json_decode( $res['id_sds'], true );

      $ar = array( "table" => "ssm.ssm_scuole", "value" => "id", "text" => "nome_scuola", "order" => "nome_scuola", "where" => "idstatus=1" );
      $res['scuole_list'] = $Utils->_combo_list( $ar, true, "" );

      $response->getBody()->write( json_encode( $res ) );
      return $response;
  });

  // new
  $group->put('', function (Request $request, Response $response) use ($crud ) {
    $log = new Logger();

    $p = json_decode($request->getBody(), true);

    $retValidate = validate( "ateneo", $p );
    if( $retValidate != "" ) {
      $response->getBody()->write( $retValidate );
      return $response
        ->withStatus(400)
        ->withHeader('Content-Type', 'text/plain');
    }

    $scuole_selected = $p['id_sds'];

    // json scuole
    $p['id_sds'] = json_encode( $p['id_sds'] );
    $res = $crud->record_new( $p );
    $log->log( "NUOVO ATENEO " . json_encode( $res ) );

    // crea i record sulla relazione scuole/ateneo
    //$scuole_selected = json_decode( $p['id_sds'], true );
    scuole_relation_set( $res['id'], $scuole_selected );

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
  $group->post('/{id}', function (Request $request, Response $response, $args) use ($crud) {
    $p = json_decode($request->getBody(), true);

    $retValidate = validate( "ateneo", $p );
    if( $retValidate != "" ) {
      $response->getBody()->write( $retValidate );
      return $response
        ->withStatus(400)
        ->withHeader('Content-Type', 'text/plain');
    }

    $scuole_selected = $p['id_sds'];

    // json scuole
    $p['id_sds'] = json_encode( $p['id_sds'] );

    $res = $crud->record_update( $args['id'], $p );

    // crea i record sulla relazione scuole/ateneo
    scuole_relation_set( $args['id'], $scuole_selected );


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
  $group->delete('/{id}', function (Request $request, Response $response, $args) use ($crud) {
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
})->add($authMW);

// START: Gestione url sso e helpdesk

// SSO
$app->get('/atenei/url/sso', function (Request $request, Response $response, $args) use ($crud) {
  $Utils = new Utils();
  $p = $request->getQueryParams();

  $arSql = array(
    "log" => true,
    "select" => [
      "url_sso",
      "url_sso_preprod",
      "dominio",
      "dominio_preprod"
    ],
    "from" => "ssm.ssm_atenei",
    "where" => [
      [
        "field" => "dominio",
        "value" => $p['domain'],
        "operatorAfter" => "OR"
      ],
      [
        "field" => "dominio_preprod",
        "value" => $p['domain']
      ]
    ]
  );
  $arrSql = $Utils->dbSelect($arSql);
  $d = $arrSql['data'][0];
  $url = '';
  if ($d['dominio'] == $p['domain']) {
      $url = $d['url_sso'];
  } else {
      $url = $d['url_sso_preprod'];
  }

  $response->getBody()->write(json_encode(array("url" => $url)));
  return $response->withHeader('Content-Type', 'application/json');
});

// HelpDesk
$app->get('/atenei/url/helpdesk', function (Request $request, Response $response, $args) use ($crud) {
  $Utils = new Utils();
  $p = $request->getQueryParams();

  $arSql = array(
    "select" => [
      "url_helpdesk",
      "url_helpdesk_preprod",
      "dominio",
      "dominio_preprod"
    ],
    "from" => "ssm.ssm_atenei",
    "where" => [
      [
        "field" => "dominio",
        "value" => $p['domain'],
        "operatorAfter" => "OR"
      ],
      [
        "field" => "dominio_preprod",
        "value" => $p['domain']
      ]
    ]
  );
  $arrSql = $Utils->dbSelect($arSql);
  $d = $arrSql['data'][0];
  $url = '';
  if ($d['dominio'] == $p['domain']) {
      $url = $d['url_helpdesk'];
  } else {
      $url = $d['url_helpdesk_preprod'];
  }

  $response->getBody()->write(json_encode(array("url" => $url)));
  return $response;
});

// Logout
$app->get('/atenei/url/logout', function (Request $request, Response $response, $args) use ($crud) {
  $Utils = new Utils();
  $p = $request->getQueryParams();

  $arSql = array(
    "select" => [
      "url_logout",
      "url_logout_preprod",
      "dominio",
      "dominio_preprod"
    ],
    "from" => "ssm.ssm_atenei",
    "where" => [
      [
        "field" => "dominio",
        "value" => $p['domain'],
        "operatorAfter" => "OR"
      ],
      [
        "field" => "dominio_preprod",
        "value" => $p['domain']
      ]
    ]
  );
  $arrSql = $Utils->dbSelect($arSql);
  $d = $arrSql['data'][0];
  $url = '';
  if ($d['dominio'] == $p['domain']) {
      $url = $d['url_logout'];
  } else {
      $url = $d['url_logout_preprod'];
  }

  $response->getBody()->write(json_encode(array("url" => $url)));
  return $response;
});


function scuole_relation_set( $idateneo, $scuole_selected ) {
  $log = new Logger();
  $Utils = new Utils();
  $uuid = new UUID();

  $log->log( "scuole_selected - " . $idateneo . " - " . json_encode( $scuole_selected ) );

  foreach( $scuole_selected as $v ) {
    $log->log( "scuola: " . $v );

    // cerca scuola_atenei
    $idscuola_ateneo = scuola_ateneo_search( $idateneo, $v );

    $log->log( "idscuola_ateneo " . $idscuola_ateneo );

    if( $idscuola_ateneo != "" ) {
      $log->log( "Relazione scuola ateneo già esistente" );
    } else {

      $log->log( "ateneo: " . $idateneo . " - " . $k . " - " . $v );
      $ar['idateneo'] = $idateneo;
      $ar['idscuola'] = $v;
      $ar['id'] = $uuid->v4();
      $ar['idstatus'] = 1;
      $ar['date_create'] = "now()";
      $ar['date_update'] = "now()";

      $log->log( json_encode( $ar ) );
      $ret = $Utils->dbSql( true, "ssm.ssm_scuole_atenei", $ar, "", "" );

      // crea il record prestazioni
      $addPrestazioni = array(
        "id" => $uuid->v4(),
        "idscuola" => $ar['id'],
        "idtipo" => 1,
        "nome" => "Prestazioni",
        "idstatus" => 1,
        "date_create" => "now()",
        "date_update" => "now()"
      );
      $ret2 = $Utils->dbSql( true, "ssm.ssm_registrazioni_combo", $addPrestazioni, "", "" );
      $log->log( "Aggiunge prestazione - " . json_encode( $addPrestazioni ) );
      $log->log( "Aggiunge prestazione out - " . json_encode( $ret2 ) );


    }

  }


}

function scuola_ateneo_search( $idateneo, $idscuola ) {
  $db = new dataBase();

  $sql = sprintf( "SELECT id
    FROM ssm.ssm_scuole_atenei
    WHERE idateneo='%s' AND idscuola='%s' AND idstatus=1",
    $db->real_escape_string( $idateneo ),
    $db->real_escape_string( $idscuola ) );
  $db->query( $sql );
  $rec = $db->fetchassoc();
  return $rec['id'];

}


?>

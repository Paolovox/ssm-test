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




$app->group('/unita_operative', function (RouteCollectorProxy $group) use ($auth) {

  $data = [
    "table" => "ssm.ssm_unita_operative",
    "id" => "id",
    "sort" => "nome",
    "order" => "asc",
    "status_field" => "idstatus",
    "update_field" => "date_update",
    "create_field" => "date_create",
    "list_fields" => [ "ssm.ssm_unita_operative.id", "ssm.ssm_unita_operative.nome", "pre.nome as nome_presidio" ],
    "list_join" => [
      [
          "ssm.ssm_presidi pre",
          " ssm.ssm_unita_operative.idpresidio=pre.id "
      ]
    ]

  ];


  $crud = new CRUD( $data );

  // list
  $group->get('', function (Request $request, Response $response) use ($auth, $crud) {
      $log = new Logger();
      $p = $request->getQueryParams();

      if( $p['srt'] == "nome" )
        $p['srt'] = "ssm.ssm_unita_operative.nome";

      if( $p['s'] != "" ) {
        $p['search'] = array(
          "field" => "ssm.ssm_unita_operative.nome",
          "operator" => " LIKE ",
          "value" => "%" . $p['s'] . "%"
        );
      }

      $log->log( "UnitaOperative list " . json_encode( $p ) );

      $p['_ssm.ssm_unita_operative.idstatus'] = "1";
      $res = $crud->record_list( $p );
      $response->getBody()->write( json_encode( $res ) );
      return $response;
  });

  // get
  $group->get('/{id}', function (Request $request, Response $response, $args) use ($crud) {
    $Utils = new Utils();
    $res = $crud->record_get( $args['id'] )['data'][0];
    if( !$res )
      $res = [];

    $arSql = array(
      "select" => ["CONCAT(sp.nome, ' (', sa.nome, ')') as text", "sp.id as id"],
      "from" => "ssm.ssm_presidi sp",
      "join" => [
        [
          "ssm.ssm_aziende sa",
          "sa.id=sp.idazienda"
        ]
      ],
      "order" => "sp.nome asc"
    );

    $arrSql = $Utils->dbSelect($arSql);
    $ar = array( "table" => "ssm.ssm_presidi", "value" => "id", "text" => "nome", "order" => "nome", "where" => "idstatus=1" );
    $res['presidi_list'] = $arrSql['data'];

    $response->getBody()->write( json_encode( $res ) );
    return $response;
  });

  // new
  $group->put('', function (Request $request, Response $response) use ($crud ) {
    $p = json_decode($request->getBody(), true);

    $retValidate = validate( "unita_operative", $p );
    if( $retValidate != "" ) {
      $response->getBody()->write( $retValidate );
      return $response
        ->withStatus(400)
        ->withHeader('Content-Type', 'text/plain');
    }

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
  $group->post('/{id}', function (Request $request, Response $response, $args) use ($crud) {
    $log = new Logger();

    $p = json_decode($request->getBody(), true);

    $retValidate = validate( "unita_operative", $p );
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

});



?>

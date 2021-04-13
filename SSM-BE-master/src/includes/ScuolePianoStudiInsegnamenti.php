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


$app->group('/pds_insegnamenti', function (RouteCollectorProxy $groupInsegnamenti) use ($auth, $args) {


  $data = [
    "table" => "ssm.ssm_scuole_pds_insegnamenti",
    "id" => "id",
    "sort" => "ssm.ssm_scuole_pds_insegnamenti.nome",
    "order" => "asc",
    "status_field" => "idstatus",
    "update_field" => "date_update",
    "create_field" => "date_create",
    "list_fields" => [ "ssm.ssm_scuole_pds_insegnamenti.id", "ssm.ssm_scuole_pds_insegnamenti.cfu", "ssm.ssm_scuole_pds_insegnamenti.nome", "concat(ut.nome, ' ', ut.cognome) as docente_nome"],
    "list_join" => [
      [
          "ssm_utenti ut",
          " ut.id=ssm.ssm_scuole_pds_insegnamenti.iddocente "
      ]
    ]
  ];


  $crud = new CRUD( $data );



  // list
  $groupInsegnamenti->get('/{idscuola_specializzazione}/{idpiano_studi}', function (Request $request, Response $response, $args) use ($auth, $crud) {

      $p = $request->getQueryParams();
      $p['_ssm.ssm_scuole_pds_insegnamenti.idstatus'] = 1;
      $p['_idscuola_specializzazione'] = $args['idscuola_specializzazione'];
      $p['_idpiano_studi'] = $args['idpiano_studi'];
      $res = $crud->record_list( $p );

      $response->getBody()->write( json_encode( $res ) );
      return $response;
  });

  // get
  $groupInsegnamenti->get('/{idscuola_specializzazione}/{idpiano_studi}/{id}', function (Request $request, Response $response, $args) use ($crud) {
      $Utils = new Utils();
      $log = new Logger();

      $log->log( "get - " . $args['idscuola_specializzazione'] . " - " . $args['id'] );

      $res = $crud->record_get( $args['id'] )['data'][0];

      $arSelect = $Utils->dbSelect( [
        "log" => true,
        "select" => [ "ut.id", "concat(nome, ' ', cognome) as text"],
        "from" => "ssm_utenti_ruoli_lista url",
        "join" => [
          [
            "ssm_utenti ut",
            "ut.id=url.idutente"
          ],
          [
            "ssm.ssm_scuole_atenei sa",
            "sa.idateneo=url.idateneo"
          ],
        ],
        "where" => [
          [
          "field" => "sa.id",
          "operator" => "=",
          "value" => $args['idscuola_specializzazione'],
          "operatorAfter" => " AND "
          ],
          [
          "field" => "url.idruolo",
          "operator" => "=",
          "value" => 10,
          "operatorAfter" => " AND "
          ],
          [
          "field" => "ut.idstatus",
          "operator" => "=",
          "value" => 1
          ]
        ],

      ]);


      $log->log( "arSelect 2 " . json_encode( $arSelect ) );
      $ar = array();
      foreach( $arSelect['data'] as $k => $v )
        $ar[] = $v;

      $res['docenti_list'] = $ar;

      $response->getBody()->write( json_encode( $res ) );
      return $response;
  });

  // new
  $groupInsegnamenti->put('/{idscuola_specializzazione}/{idpiano_studi}', function (Request $request, Response $response, $args) use ($crud ) {
    $log = new Logger();

    $p = json_decode($request->getBody(), true);
    $log->log( "put - " . json_encode( $args ) . " - " . json_encode( $p ) );

    $p['idscuola_specializzazione'] = $args['idscuola_specializzazione'];
    $p['idpiano_studi'] = $args['idpiano_studi'];

    $retValidate = validate( "ssm_pds_insegnamenti", $p );
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
  $groupInsegnamenti->post('/{idscuola_speciaizzazione}/{idpiano_studi}/{id}', function (Request $request, Response $response, $args) use ($crud) {
    $log = new Logger();

    $p = json_decode($request->getBody(), true);

    $retValidate = validate( "ssm_pds_insegnamenti", $p );
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
  $groupInsegnamenti->delete('/{idscuola_specializzazione}/{idpiano_studi}/{id}', function (Request $request, Response $response, $args) use ($crud) {
    $log = new Logger();
    $log->log( "Elimina insegnamento " . $args['id'] );
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

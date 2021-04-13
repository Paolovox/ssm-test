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




$app->group('/scuole_di_specializzazione', function (RouteCollectorProxy $group) use ($auth) {

  $data = [
    "table" => "ssm.ssm_scuole_atenei",
    "id" => "id",
    "sort" => "nome_scuola",
    "order" => "asc",
    "status_field" => "idstatus",
    "update_field" => "date_update",
    "create_field" => "date_create",
    "list_fields" => [ "ssm.ssm_scuole_atenei.id", "ssm.ssm_scuole_atenei.idscuola", "ssm.ssm_scuole_atenei.idateneo", "sc.nome_scuola", "at.nome_ateneo", "ssm.ssm_scuole_atenei.telefono", "ssm.ssm_scuole_atenei.email" ],
    "list_join" => [
      [
          "ssm.ssm_scuole sc",
          " ssm.ssm_scuole_atenei.idscuola=sc.id "
      ],
      [
          "ssm.ssm_atenei at",
          " ssm.ssm_scuole_atenei.idateneo=at.id "
      ]
    ]

  ];
  $crud = new CRUD( $data );


  // list
  $group->get('/{idateneo}', function (Request $request, Response $response, $args) use ($auth, $crud) {
      $log = new Logger();

      $user = $request->getAttribute('user');
      $p = $request->getQueryParams();
      $log->log( "scuole_di_specializzazione - list " . json_encode( $p ) );

      $p['_ssm.ssm_scuole_atenei.idstatus'] = "1";
      $p['_idateneo'] = $args['idateneo'];

      if($user['idruolo'] == 9)  {
        $p['_ssm.ssm_scuole_atenei.id'] = $user['idscuola'];
      }

      if( $p['s'] != "" ) {
        $p['search'] = array(
          "field" => "sc.nome_scuola",
          "operator" => " LIKE ",
          "value" => "%" . $p['s'] . "%",
        );
      }

      $res = $crud->record_list( $p );
      $response->getBody()->write( json_encode( $res ) );
      return $response;
  });

  // get
  $group->get('/{idscuola_relazione}/{idateneo}', function (Request $request, Response $response, $args) use ($crud) {
      $res = $crud->record_get( $args['idscuola_relazione'] )['data'][0];
      if( !$res )
        $res = [];

      $res['nome_scuola'] = nome_scuola_get( $res['idscuola'] );
      $res['manutenzione_status'] = $res['manutenzione_status'] == 1?true:false;

      $response->getBody()->write( json_encode( $res ) );
      return $response;
  });

  // new
  $group->put('/{idscuola_relazione}/{idateneo}', function (Request $request, Response $response) use ($crud ) {
    $p = json_decode($request->getBody(), true);

    $retValidate = validate( "scuole_relazione", $p );
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
  $group->post('/{idscuola_relazione}/{idateneo}', function (Request $request, Response $response, $args) use ($crud) {
    $log = new Logger();
    $p = json_decode($request->getBody(), true);

    $log->log( json_encode( $p ) );
    $p['manutenzione_status'] = $p['manutenzione_status'] == true?1:0;

    $retValidate = validate( "scuole_relazione", $p );
    if( $retValidate != "" ) {
      $response->getBody()->write( $retValidate );
      return $response
        ->withStatus(400)
        ->withHeader('Content-Type', 'text/plain');
    }

    $res = $crud->record_update( $args['idscuola_relazione'], $p );
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


function nome_scuola_get( $idscuola ) {
  $db = new dataBase();
  $sql = sprintf( "SELECT nome_scuola FROM ssm.ssm_scuole WHERE id='%s'", $db->real_escape_string( $idscuola ) );

  $db->query( $sql );
  $rec = $db->fetchassoc();
  return $rec['nome_scuola'];
}




?>

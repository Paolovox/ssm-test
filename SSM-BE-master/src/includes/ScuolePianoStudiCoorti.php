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


$app->group('/pds_coorti', function (RouteCollectorProxy $groupCoorti) use ($auth, $args) {


  $data = [
    "table" => "ssm.ssm_pds_coorti",
    "id" => "id",
    "sort" => "ssm.ssm_pds_coorti.nome",
    "order" => "asc",
    "status_field" => "idstatus",
    "update_field" => "date_update",
    "create_field" => "date_create",
    "list_fields" => [ "ssm.ssm_pds_coorti.id", "ssm.ssm_pds_coorti.nome",
      "date_format(data_inizio,'%d-%m-%Y') as data_inizio_text",
      "date_format(data_fine,'%d-%m-%Y') as data_fine_text"]
  ];


  $crud = new CRUD( $data );



  // list
  $groupCoorti->get('/{idscuola_specializzazione}', function (Request $request, Response $response, $args) use ($auth, $crud) {

      $p = $request->getQueryParams();
      $p['_ssm.ssm_pds_coorti.idstatus'] = 1;
      $p['_idscuola_specializzazione'] = $args['idscuola_specializzazione'];

      if( $p['s'] != "" ) {
        $p['search'] = array(
          "field" => "ssm.ssm_pds_coorti.nome",
          "operator" => " LIKE ",
          "value" => "%" . $p['s'] . "%"
        );
      }

      $res = $crud->record_list( $p );

      $response->getBody()->write( json_encode( $res ) );
      return $response;
  });



  // get
  $groupCoorti->get('/{idscuola_specializzazione}/{id}', function (Request $request, Response $response, $args) use ($crud) {
    $Utils = new Utils();
    $log = new Logger();

    $log->log( "get - " . $args['idscuola_specializzazione'] . " - " . $args['id'] );
    $res = $crud->record_get( $args['id'] )['data'][0];
    if( !$res )
      $res = [];


    $response->getBody()->write( json_encode( $res ) );
    return $response;
  });


  // new
  $groupCoorti->put('/{idscuola_specializzazione}', function (Request $request, Response $response, $args) use ($crud ) {
    $log = new Logger();

    $p = json_decode($request->getBody(), true);
    $log->log( "put - " . json_encode( $args ) . " - " . json_encode( $p ) );

    $p['idscuola_specializzazione'] = $args['idscuola_specializzazione'];
    $p['data_inizio'] = substr( $p['data_inizio'], 0, 10 );
    $p['data_fine'] = date( "Y-m-d", strtotime( $p['data_inizio'] ) + (365*24*60*60) );

    $retValidate = validate( "ssm.ssm_pds_coorti", $p );
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
  $groupCoorti->post('/{idscuola_speciaizzazione}/{id}', function (Request $request, Response $response, $args) use ($crud) {
    $log = new Logger();

    $p = json_decode($request->getBody(), true);
    $p['data_inizio'] = substr( $p['data_inizio'], 0, 10 );
    $p['data_fine'] = date( "Y-m-d", strtotime( $p['data_inizio'] ) + (365*24*60*60) );

    $retValidate = validate( "ssm.ssm_pds_coorti", $p );
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
  $groupCoorti->delete('/{idscuola_specializzazione}/{id}', function (Request $request, Response $response, $args) use ($crud) {
    $log = new Logger();
    $log->log( "Elimina coorte " . $args['id'] );
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


  // get
  $groupCoorti->get('/contatori/{idscuola_specializzazione}/{idcoorte}', function (Request $request, Response $response, $args) use ($crud) {
    $log = new Logger();

    $log->log( "Coorte list - " . json_encode( $args ) );

    $p = $request->getQueryParams();



    if( $p['s'] != "" ) {
      $p['search'] = array(
        "field" => "ssm.ssm_pds_coorti_contatori.nome",
        "operator" => " LIKE ",
        "value" => "%" . $p['s'] . "%"
      );
    }

    $data = [
      "table" => "ssm.ssm_pds_coorti_contatori",
      "id" => "id",
      "sort" => "ssm.ssm_pds_coorti_contatori.nome",
      "order" => "asc",
      "status_field" => "idstatus",
      "update_field" => "date_update",
      "create_field" => "date_create",
      "list_fields" => [ "id", "nome", "struttura_text", "quantita"]
    ];

    $crudContatori = new CRUD( $data );
    $p['_ssm.ssm_pds_coorti_contatori.idstatus'] = 1;
    $p['_ssm.ssm_pds_coorti_contatori.idscuola_specializzazione'] = $args['idscuola_specializzazione'];
    $p['_ssm.ssm_pds_coorti_contatori.idcoorte'] = $args['idcoorte'];

    $res = $crudContatori->record_list( $p );

    $response->getBody()->write( json_encode( $res ) );
    return $response;

  });

  // get
  $groupCoorti->get('/contatori/{idscuola_specializzazione}/{idcoorte}/{id}', function (Request $request, Response $response, $args) use ($crud) {
      $Utils = new Utils();
      $log = new Logger();

      $log->log( "get - " . $args['idscuola_specializzazione'] . " - " . $args['id'] );
      $data = [
        "table" => "ssm.ssm_pds_coorti_contatori",
        "id" => "id",
        "sort" => "ssm.ssm_pds_coorti_contatori.nome",
        "order" => "asc",
        "status_field" => "idstatus",
        "update_field" => "date_update",
        "create_field" => "date_create",
        "list_fields" => [ "id", "nome", "struttura_text"]
      ];

      $crudContatori = new CRUD( $data );

      $log->log( "get contatori - " . $args['idscuola_specializzazione'] . " - " . $args['id'] );
      $res = $crudContatori->record_get( $args['id'] )['data'][0];
      if( !$res )
        $res = [];


      $arSelect = $Utils->dbSelect( [
        "select" => [ "ci.id as id", "ci.nome as text", "co.id as co_id", "co.nome as co_text"],
        "from" => "ssm.ssm_registrazioni_combo co",
        "join" => [
          [
            "ssm.ssm_registrazioni_combo_items ci",
            "ci.idcombo=co.id"
          ],
        ],
        "where" => [
          [
          "field" => "co.idscuola",
          "operator" => "=",
          "value" => $args['idscuola_specializzazione'],
          "operatorAfter" => " AND "
          ],
          [
          "field" => "co.idstatus",
          "operator" => "=",
          "value" => 1,
          "operatorAfter" => " AND "
          ],
          [
          "field" => "ci.idstatus",
          "operator" => "=",
          "value" => 1
          ],

        ],
        "order" => "co.nome,ci.nome"
      ]);

      // INFO: Storicizzazione linea 276
      $struttura = json_decode($res['struttura'], true);

      $ar = array();
      foreach( $arSelect['data'] as $k => $v ) {
        if( $o != "" && $v['co_id'] != $o ) {
          $out[] = array(
            "id" => $in[0]['co_id'],
            "nome" => $in[0]['co_text'],
            "options" => $in
          );
          // TODO: Capire se attivarlo, perderemmo la storicizzazione...
          foreach ($struttura as $key => $value) {
            if ($value['id'] == $in[0]['co_id'])  {
              $struttura[$key]['options'] = $in;
            }
          }
          unset( $in );
        }
        $o = $v['co_id'];
        $in[] = $v;
      }

      // INFO: Storicizzazione linea 276
      $res['struttura'] = json_encode($struttura);

      if( $in != "" ) {
        $out[] = array(
          "id" => $in[0]['co_id'],
          "nome" => $in[0]['co_text'],
          "options" => $in
        );
      }

      $res['attivita_list'] = $out;
      $res['autonomia'] = json_decode( $res['autonomia'], true );
      $res['frequenza'] = $res['frequenza'] ? true:false;

      $response->getBody()->write( json_encode( $res ) );
      return $response;
  });

  // new
  $groupCoorti->put('/contatori/{idscuola_specializzazione}/{idcoorte}/{id}', function (Request $request, Response $response, $args) use ($crud) {
    $Utils = new Utils();
    $log = new Logger();
    $uuid = new UUID();

    $p = json_decode($request->getBody(), true);
    $log->log( "put contatori  - " . json_encode( $args ) . " - " . json_encode( $p ) );

    $s = "";
    foreach( $p['data'] as $k => $v ) {
      $s .= " " . $v['nome'] . " -> ";
      foreach( $v['options'] as $a => $b ) {
        if( array_search( $b['id'], $v['idvalue'] ) !== false )
          $s .= " " . $b['text'] . " OR ";
      }
      $s = substr($s, 0, -3);
      $s .= " AND ";
    }
    $s = substr($s, 0, -4);

    $add['struttura_text'] = $s;

    if( $args['id'] == 0 )
      $add['id'] = $uuid->v4();

    $add['idscuola_specializzazione'] = $args['idscuola_specializzazione'];
    $add['idcoorte'] = $args['idcoorte'];
    $add['nome'] = $p['nome'];
    $add['quantita'] = $p['qty'];
    $add['struttura'] = json_encode( $p['data'] );
    $add['autonomia'] = $p['autonomia'] != "" ? json_encode( $p['autonomia'] ) : json_encode( [] );
    $add['frequenza'] = $p['frequenza'] ? 1:0;
    $add['idstatus'] = 1;
    $add['date_create'] = "now()";
    $add['date_update'] = "now()";

    $res = $Utils->dbSql( $args['id']=="0"?true:false, "ssm.ssm_pds_coorti_contatori", $add, "id", $args['id'] );
    $res['success'] = 1;

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


  // delete
  $groupCoorti->delete('/contatori/{idscuola_specializzazione}/{idcoorte}/{id}', function (Request $request, Response $response, $args) use ($crud) {
    $log = new Logger();
    $log->log( "Elimina contatore coorte " . $args['id'] );

    $data = [
      "table" => "ssm.ssm_pds_coorti_contatori",
      "id" => "id",
      "sort" => "ssm.ssm_pds_coorti_contatori.nome",
      "order" => "asc",
      "status_field" => "idstatus",
      "update_field" => "date_update",
      "create_field" => "date_create",
      "list_fields" => [ "id", "nome"]
    ];

    $crudContatori = new CRUD( $data );
    $res = $crudContatori->record_delete( $args['id'] );
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

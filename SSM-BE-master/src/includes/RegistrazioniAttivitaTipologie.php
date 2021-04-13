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



$app->group('/registrazioni_tipologie_attivita', function (RouteCollectorProxy $groupRegistrazioniAttivitaTipologie) use ($auth) {

  $data = [
    "table" => "ssm.ssm_registrazioni_attivita_tipologie",
    "id" => "id",
    "sort" => "ssm.ssm_registrazioni_attivita_tipologie.nome",
    "order" => "asc",
    "status_field" => "idstatus",
    "update_field" => "date_update",
    "create_field" => "date_create",
    "list_fields" => [ "ssm.ssm_registrazioni_attivita_tipologie.id", "ssm.ssm_registrazioni_attivita_tipologie.nome",
      "ss.nome as nome_settore_scientifico"],
    "list_join" => [
      [
          "ssm.ssm_settori_scientifici ss",
          " ss.id=ssm.ssm_registrazioni_attivita_tipologie.idsettore_scientifico "
      ],
    ]
  ];


  $crud = new CRUD( $data );



  // list
  $groupRegistrazioniAttivitaTipologie->get('/{idscuola}/{idcoorte}', function (Request $request, Response $response, $args) use ($auth, $crud) {

    $p = $request->getQueryParams();

    $p['_ssm.ssm_registrazioni_attivita_tipologie.idscuola'] = $args['idscuola'];
    $p['_ssm.ssm_registrazioni_attivita_tipologie.idcoorte'] = $args['idcoorte'];
    $p['_ssm.ssm_registrazioni_attivita_tipologie.idstatus'] = 1;

    if( $p['srt'] == "nome" )
      $p['srt'] = "ssm.ssm_registrazioni_attivita_tipologie.nome";

    if( $p['s'] != "" ) {
      $p['multi_search'] = array(
        [
          "field" => "ssm.ssm_registrazioni_attivita_tipologie.nome",
          "operator" => " LIKE ",
          "value" => "%" . $p['s'] . "%",
          "operatorAfter" => " OR "
        ],
        [
          "field" => "ss.nome",
          "operator" => " LIKE ",
          "value" => "%" . $p['s'] . "%"
        ]
      );
    }

    $res = $crud->record_list( $p );
    $response->getBody()->write( json_encode( $res ) );
    return $response;
  });


  // get
  $groupRegistrazioniAttivitaTipologie->get('/{idscuola_specializzazione}/{idcoorte}/{id}', function (Request $request, Response $response, $args) use ($crud) {
      $Utils = new Utils();
      $log = new Logger();
      $db = new dataBase();

      $res = $crud->record_get( $args['id'] )['data'][0];
      if( !$res )
        $res = [];

      $log->log( "args: " . json_encode( $args ) );


      /*
      $res['idpds'] = json_decode( $res['idpds'], true );
      $sql = sprintf( "SELECT pds.id, concat(ss.nome, ' - ', sad.nome, ' - ', anno, ' - ', sta.nome_tipologia, ' - q:', quantita) as text
        FROM ssm.ssm_pds pds
        LEFT JOIN ssm.ssm_pds_ambiti_disciplinari sad ON sad.id=pds.idambito_disciplinare
        LEFT JOIN ssm.ssm_settori_scientifici ss ON ss.id=pds.idsettore_scientifico
        LEFT JOIN ssm.ssm_tipologie_attivita sta ON sta.id=pds.idtipologia_attivita
        WHERE pds.idscuola_specializzazione='%s'
          AND pds.idstatus=1 ",
        $args['idscuola_specializzazione'] );

      $log->log( $sql );

      $db->query( $sql );
      $ar = [];
      while( $rec = $db->fetchassoc() ) {
        $ar[] = $rec;
      }
      $res['pds_list'] = $ar;
      */


      $sql = sprintf( "SELECT DISTINCT ss.id, ss.nome as text
        FROM ssm.ssm_pds pds
        LEFT JOIN ssm.ssm_settori_scientifici ss ON ss.id=pds.idsettore_scientifico
        WHERE pds.idscuola_specializzazione='%s'
          AND pds.idstatus=1
          ORDER BY ss.nome",
        $db->real_escape_string( $args['idscuola_specializzazione'] ) );
      $db->query( $sql );
      $ar = [];
      while( $rec = $db->fetchassoc() ) {
        $ar[] = $rec;
      }

      $log->log( "Settori scientifici list: " . $sql );

      $res['settori_scientifici_list'] = $ar;


//      $res['settori_scientifici_list'] = settori_scientifici_list( $args['idscuola_specializzazione'] );

      $response->getBody()->write( json_encode( $res ) );
      return $response;
  });

  // new
  $groupRegistrazioniAttivitaTipologie->put('/{idscuola_specializzazione}/{idcoorte}', function (Request $request, Response $response, $args) use ($crud) {
    $log = new Logger();

    $p = json_decode($request->getBody(), true);
    $p['idscuola'] = $args['idscuola_specializzazione'];
    $p['idcoorte'] = $args['idcoorte'];

    $retValidate = validate( "ssm.ssm_registrazioni_attivita_tipologie", $p );
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
  $groupRegistrazioniAttivitaTipologie->post('/{idscuola_specializzazione}/{idcoorte}/{id}', function (Request $request, Response $response, $args) use ($crud) {
    $log = new Logger();

    $p = json_decode($request->getBody(), true);

    $retValidate = validate( "ssm.ssm_registrazioni_attivita_tipologie", $p );
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
  $groupRegistrazioniAttivitaTipologie->delete('/{idscuola_specializzazione}/{idcoorte}/{id}', function (Request $request, Response $response, $args) use ($crud) {
    $log = new Logger();

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

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


/** Crud che gestisce la relazione tra scuola e unità operative di essa */


$app->group('/scuola_utenti', function (RouteCollectorProxy $groupScuolaUtenti) use ($auth) {

  $data = [
    "table" => "ssm_utenti",
    "id" => "id",
    "sort" => "concat(cognome, ' ', nome)",
    "order" => "asc",
    "status_field" => "idstatus",
    "update_field" => "date_update",
    "create_field" => "date_create",
    "list_fields" => [ "ssm_utenti.id", "concat(ssm_utenti.nome, ' ', ssm_utenti.cognome) as nome_cognome",
      "ssm_utenti.codice_fiscale",
      "ssm_utenti.matricola", "ssm_utenti.email" ],
  ];

  $crud = new CRUD( $data );



  // list
  $groupScuolaUtenti->get('/{idscuola_specializzazione}', function (Request $request, Response $response, $args) use ($auth, $crud) {
      $log = new Logger();
      $db = new dataBase();

      $p = $request->getQueryParams();
      $log->log( "params: " . json_encode( $p ) );

      $search = '';
      // TODO: SQL_INJECTION_TEST
      if( $p['s'] != "" ) {
        $search = sprintf(" AND (ute.cognome LIKE '%%%s%%' OR ute.nome LIKE '%%%s%%' OR r.nome LIKE '%%%s%%' OR pc.nome LIKE '%%%s%%')", $db->real_escape_string($p['s']), $db->real_escape_string($p['s']), $db->real_escape_string($p['s']), $db->real_escape_string($p['s']));
      }

      if( $p['srt'] == "" || $p['srt'] == "nome") {
        $p['srt'] = "ute.cognome, ute.nome";
        $p['o'] = "asc";
      }


      $sql = sprintf( "SELECT DISTINCT SQL_CALC_FOUND_ROWS ute.id, concat(ute.cognome,' ', ute.nome) as nome_utente, r.nome as ruolo, pc.nome as coorte , anno_scuola,email
        FROM ssm_utenti_ruoli_lista url
        LEFT JOIN ssm_utenti ute ON ute.id=url.idutente
        LEFT JOIN (
        SELECT * FROM ssm.ssm_scuole_unita where idscuola_specializzazione='%s' AND idstatus=1
        ) c ON url.idunita=c.idunita
        LEFT JOIN ssm.ssm_utenti_ruoli r ON r.id=url.idruolo
        LEFT JOIN ssm.ssm_pds_coorti pc ON pc.id=ute.idcoorte
        WHERE idscuola='%s' AND url.idstatus=1 AND ute.idstatus=1 %s
        ORDER BY %s %s
        LIMIT %d,%d", $db->real_escape_string( $args['idscuola_specializzazione'] ), $db->real_escape_string( $args['idscuola_specializzazione'] ), $search,
          $db->real_escape_string( $p['srt'] ), $db->real_escape_string( $p['o'] ),
        ($p['p']-1) * $p['c'], $p['c'] );

      $db->query( $sql );
      $log->log( "/scuola_utenti - " . $sql );
      while( $rec = $db->fetchassoc() ) {
        $ar[] = $rec;
      }

      $db->query( "SELECT FOUND_ROWS()" );
      $resCount = $db->fetcharray();
      $res['total'] = $resCount[0];


      $res['count'] = sizeof( $ar );
      $res['rows'] = $ar;

      $response->getBody()->write( json_encode( $res ) );
      return $response;
  });


});

function utente_ruoli_list() {
  $Utils = new Utils();

  $res = $Utils->dbSelect( [
    "select" => ["*"],
    "from" => "ssm.ssm_utenti_ruoli"
  ]);

  foreach( $res['data'] as $k => $v ) {
    $ar[$v['id']] = $v['nome'];
  }
  return $ar;

}


?>

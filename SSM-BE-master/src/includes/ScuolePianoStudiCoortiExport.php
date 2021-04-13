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
      "table" => "ssm.ssm_pds_coorti_export",
      "id" => "id",
      "sort" => "ssm.ssm_pds_coorti_export.nome",
      "order" => "asc",
      "status_field" => "idstatus",
      "update_field" => "date_update",
      "create_field" => "date_create",
      "list_fields" => [ "id", "nome", "struttura_text", "quantita"]
    ];

  $crud = new CRUD( $data );

  // get
  $groupCoorti->get('/export/{idscuola_specializzazione}/{idcoorte}', function (Request $request, Response $response, $args) use ($crud) {
    $log = new Logger();

    $log->log( "Export list - " . json_encode( $args ) );

    $p = $request->getQueryParams();

    if( $p['s'] != "" ) {
      $p['search'] = array(
        "field" => "ssm.ssm_pds_coorti_export.nome",
        "operator" => " LIKE ",
        "value" => "%" . $p['s'] . "%"
      );
    }

    $p['_ssm.ssm_pds_coorti_export.idstatus'] = 1;
    $p['_ssm.ssm_pds_coorti_export.idscuola_specializzazione'] = $args['idscuola_specializzazione'];
    $p['_ssm.ssm_pds_coorti_export.idcoorte'] = $args['idcoorte'];

    $res = $crud->record_list( $p );

    $response->getBody()->write( json_encode( $res ) );
    return $response;

  });

  // get
  $groupCoorti->get('/export/{idscuola_specializzazione}/{idcoorte}/{id}', function (Request $request, Response $response, $args) use ($crud) {
      $Utils = new Utils();
      $log = new Logger();

      $log->log( "get - " . $args['idscuola_specializzazione'] . " - " . $args['id'] );

      $log->log( "get export - " . $args['idscuola_specializzazione'] . " - " . $args['id'] );
      $res = $crud->record_get( $args['id'] )['data'][0];
      if( !$res )
        $res = [];


      $arSelect = $Utils->dbSelect( [
        "log" => true,
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
          ]
        ],
        "order" => "co.nome,ci.nome"
      ]);

      // INFO: Storicizzazione linea 120
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
          $log->log(json_encode($struttura));
          foreach ($struttura as $k => $s) {
            foreach ($s as $key => $value) {
                if ($value['id'] == $in[0]['co_id']) {
                    $struttura[$k][$key]['options'] = $in;
                }
            }
          }
          $log->log("Strutt: " . json_encode($struttura));
          unset( $in );
        }
        $o = $v['co_id'];
        $in[] = $v;
      }

      if( $in != "" ) {
        $out[] = array(
          "id" => $in[0]['co_id'],
          "nome" => $in[0]['co_text'],
          "options" => $in
        );
      }

      // INFO: Storicizzazione linea 120
      $res['struttura'] = $struttura;

      // Prendo anche la lista delle attività
      $attivita_list = _get_id_combos_attivita($args['idscuola_specializzazione'], $args['idcoorte']);
      $out[] = array(
          "id" => "idattivita",
          "nome" => "Attività",
          "options" => $attivita_list
        );


      $res['attivita_list'] = $out;
      $res['frequenza'] = $res['frequenza'] ? true:false;

      // TODO: Capire se attivarlo o no (questa funzione controlla che vengano rimossi dai valori i valori delle opzioni che sono state eliminate)
      // $res['struttura'] = strutturaCheck($res['struttura'], $res['attivita_list']);
      $res['struttura'] = $res['struttura'];

      $response->getBody()->write( json_encode( $res ) );
      return $response;
  });

  // new
  $groupCoorti->put('/export/{idscuola_specializzazione}/{idcoorte}/{id}', function (Request $request, Response $response, $args) use ($crud) {
    $Utils = new Utils();
    $log = new Logger();
    $uuid = new UUID();

    $p = json_decode($request->getBody(), true);
    $log->log( "put export  - " . json_encode( $args ) . " - " . json_encode( $p ) );

    $s = "";
    foreach( $p['data'] as $k => $v ) {
      $s .= " OR ";
      foreach ($v as $kk => $vv) {
        $s .= " " . $vv['nome'] . " -> ";
        foreach ($vv['options'] as $a => $b) {
            if (array_search($b['id'], $vv['idvalue']) !== false) {
                $s .= " " . $b['text'] . " OR ";
            }
        }
        $s = substr($s, 0, -3);
        $s .= " AND ";
      }
      $s = substr($s, 0, -4);
      $s = substr($s, 4);
    }
    $add['struttura_text'] = $s;

    if( $args['id'] == 0 )
      $add['id'] = $uuid->v4();

    $add['idscuola_specializzazione'] = $args['idscuola_specializzazione'];
    $add['idcoorte'] = $args['idcoorte'];
    $add['nome'] = $p['nome'];
    $add['quantita'] = $p['qty'];
    $add['struttura'] = json_encode( $p['data'] );
    $add['frequenza'] = $p['frequenza'] ? 1:0;
    $add['idstatus'] = 1;
    $add['date_create'] = "now()";
    $add['date_update'] = "now()";

    $res = $Utils->dbSql( $args['id']=="0"?true:false, "ssm.ssm_pds_coorti_export", $add, "id", $args['id'] );
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
  $groupCoorti->delete('/export/{idscuola_specializzazione}/{idcoorte}/{id}', function (Request $request, Response $response, $args) use ($crud) {
    $log = new Logger();
    $log->log( "Elimina contatore coorte " . $args['id'] );

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

  $groupCoorti->put('/export/{idscuola_specializzazione}/{idcoorte}/order', function (Request $request, Response $response, $args) use ($crud) {
    $log = new Logger();
    $db = new dataBase();

    $user = $request->getAttribute('user');
    $log->log("user - " . json_encode($user));

    $p = json_decode($request->getBody(), true);
    $log->log("order - " . json_encode($p));

    // prende gli id dei piatti coinvolti
    $sql = sprintf("SELECT id,norder FROM products WHERE id='%s' OR id='%s'", $db->real_escape_string( $p['cur_id'] ), $db->real_escape_string( $p['des_id'] ) );
    $db->query($sql);
    while ($rec = $db->fetchassoc()) {
        if ($rec['id'] == $p['cur_id']) {
            $p['cur_pos'] = $rec['norder'];
        }
        if ($rec['id'] == $p['des_id']) {
            $p['des_pos'] = $rec['norder'];
        }
        $log->log("POSIZIONI: " . json_encode($p));
    }

    $log->log($p['cur_pos'] . '->' . $p['des_pos']);
    $move = $p['cur_pos'] > $p['des_pos'] ? 'up' : 'down';
    $sql = sprintf("UPDATE products SET norder=%d WHERE idcompany='%s' AND id='%s'", $p['des_pos'], $db->real_escape_string( $user['idcompany'] ), $db->real_escape_string( $p['cur_id'] ) );
    $log->log($sql);
    $db->query($sql);

    if ($p['cur_pos'] > $p['des_pos']) {
        $sql = sprintf("UPDATE products
          SET norder=norder+1
          WHERE idcompany='%s'
            AND norder>=%d AND norder<%d and id!='%s' ORDER BY norder", $db->real_escape_string( $user['idcompany'] ), $p['des_pos'], $p['cur_pos'], $db->real_escape_string( $p['cur_id'] ));
        $log->log($sql);
        $db->query($sql);
    }

    if ($p['cur_pos'] < $p['des_pos']) {
        $sql = sprintf("UPDATE products
          SET norder=norder-1
          WHERE idcompany='%s'
            AND norder<=%d AND norder>%d and id!='%s' ORDER BY norder", $db->real_escape_string( $user['idcompany'] ), $p['des_pos'], $p['cur_pos'], $db->real_escape_string( $p['cur_id'] ) );
        $log->log($sql);
        $db->query($sql);
    }

    return $response
        ->withStatus(200)
        ->withHeader('Content-Type', 'application/json');
  });


});

function strutturaCheck($struttura, $attivitaList)  {
  $struttura = json_decode($struttura, true);
  $log = new Logger();

  foreach ($struttura as $key => $value) {
    foreach ($value as $k => $v) {
      foreach ($attivitaList as $vv) {
        if ($vv['id'] == $v['id'])  {
          foreach ($v['idvalue'] as $keyO => $o) {
            $found = false;
            foreach ($vv['options'] as $keyO2 => $o2) {
              if ($o2['id'] == $o)  {
                $found = true;
                break;
              }
            }
            if (!$found)  {
              $values = $struttura[$key][$k]['idvalue'];
              array_splice($values, $keyO, 1);
              $struttura[$key][$k]['idvalue'] = $values;
            }
          }
          $struttura[$key][$k]['options'] = $vv['options'];
          break;
        }
      }
    }
  }
  return $struttura;
}


?>

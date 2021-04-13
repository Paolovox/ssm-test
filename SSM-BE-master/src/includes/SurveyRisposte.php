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




$app->group('/survey_risposte', function (RouteCollectorProxy $groupSurveyRisposte) use ($auth) {


      // list
      $groupSurveyRisposte->get('/{idsurvey}', function (Request $request, Response $response, $args) use ($auth, $crud) {
          $db = new dataBase();

          $user = $request->getAttribute('user');
          $p = $request->getQueryParams();

          $sql = sprintf( "SELECT sud.*, ss.titolo
            FROM ssm_survey_domande sud
            LEFT JOIN ssm_survey ss ON ss.id=sud.idsurvey
            WHERE sud.idsurvey='%s'
              AND sud.idstatus=1
              AND sud.idstatus_domanda=1", $db->real_escape_string( $args['idsurvey'] ) );
          $db->query( $sql );

          $nDomanda = 1;
          while( $rec = $db->fetchassoc() ) {
            if( $titolo == "" )
              $titolo = $rec['titolo'];

            $out['selectOptions'] = "domanda_" . $nDomanda;
            $out['required'] = $rec['obbligatorio'] == 1 ? true : false;
            $out['name'] = $rec['id'];
            $out['placeholder'] = $rec['domanda'];

            switch( $rec['idtipo_risposta'] ) {
              case 1: // TESTO
                $out['type'] = "TEXTAREA";
                break;
              case 2: // SELEZIONE SINGOLA
                $out['type'] = "SELECT";
                $o = json_decode( $rec['risposte'], true );
                foreach( $o as $k => $v ) {
                  $r[] = array( "id" => $v, "text" => $v );
                }
                $res['selectOptions']["domanda_" . $nDomanda] = $r;
                break;
              case 3: // SELEZIONE MULTIPLA
                $out['type'] = "SELECT";
                $o = json_decode( $rec['risposte'], true );
                foreach( $o as $k => $v ) {
                  $r[] = array( "id" => $v, "text" => $v );
                }
                $res['selectOptions']["domanda_" . $nDomanda] = $r;
                $out['selectMultiple'] = true;
                break;
              case 4: // SI/NO
                $out['type'] = "SELECT";
                $res['selectOptions']["domanda_" . $nDomanda] = array(
                  array( "id" => "Si", "text" => "Si" ),
                  array( "id" => "No", "text" => "No" )
                );
                $out['selectMultiple'] = false;
                break;
            }

            $nDomanda++;

            $r = array();
            $res['dialogFields'][] = $out;
          }

          $res['titolo'] = $titolo;

          $response->getBody()->write( json_encode( $res ) );
          return $response;
      });


      // salva survey
      $groupSurveyRisposte->put('/{idsurvey}', function (Request $request, Response $response, $args) use ($crud ) {
        $log = new Logger();
        $Utils = new Utils();

        $p = json_decode($request->getBody(), true);
        $user = $request->getAttribute('user');

        foreach( $p as $iddomanda => $risposte ) {
          $ar = array(
            "idsurvey" => $args['idsurvey'],
            "iduser" => $user['id'],
            "iddomanda" => $iddomanda,
            "risposta" => json_encode( $risposte ),
            "data_risposta" => "now()"
          );

          $ret = $Utils->dbSql( true, "ssm_survey_risposte", $ar, "", "" );
          if( $ret['success'] != 1 ) {
            $response->getBody()->write( "Errore nel salvataggio della survey" . $sql );
            return $response
              ->withStatus(400)
              ->withHeader('Content-Type', 'text/plain');
          }

        }

        $response->getBody()->write( json_encode( $res ) );
          return $response
            ->withStatus(200)
            ->withHeader('Content-Type', 'application/json');

      });


      // visualizza la lista degli specializzandi che hanno risposto alla survey X
      $groupSurveyRisposte->get('/specializzandi/{idsurvey}', function (Request $request, Response $response, $args) use ($auth, $crud) {
        $db = new dataBase();

        $user = $request->getAttribute('user');
        $p = $request->getQueryParams();

        $sql = sprintf( "SELECT SQL_CALC_FOUND_ROWS
            DISTINCT sur.idsurvey, sur.iduser,
            concat(su.cognome, ' ', su.nome) as nome_specializzando,
            DATE_FORMAT( sur.data_risposta, '%%d-%%m-%%Y') as data_risposta,
            DATE_FORMAT( sur.data_risposta, '%%H:%%i:%%s') as ora_risposta,
            ss.titolo
          FROM ssm_survey_risposte sur
          LEFT JOIN ssm_survey ss ON ss.id=sur.idsurvey AND ss.idstatus=1
          LEFT JOIN ssm_utenti su ON su.id=sur.iduser
          WHERE ss.idscuola='%s' AND sur.idsurvey='%s'
          ORDER BY su.cognome,su.nome
          LIMIT %d,%d",
            $db->real_escape_string( $user['idscuola'] ),
            $db->real_escape_string( $args['idsurvey'] ),
            ($p['p']-1) * $p['c'], $p['c'] );

        $db->query( $sql );
        while( $rec = $db->fetchassoc() ) {
          if( $titolo == "" ) {
            $titolo = $rec['titolo'];
          }

          switch( $user['idruolo'] ) {
            case 8: // specializzando
            case 7: // tutor
              $rec['canViewRisposte'] = true;
              $rec['canViewReport'] = false;
              break;

            case 9: // segreteria
            case 2: // direttore ateneo
              $rec['canViewRisposte'] = false;
              $rec['canViewReport'] = true;
              break;
          }

          $ar[] = $rec;
        }

        $db->query( "SELECT FOUND_ROWS()" );
        $resCount = $db->fetcharray();
        $res['total'] = $resCount[0];
        $res['count'] = sizeof( $ar );
        $res['rows'] = $ar;
        $res['titolo'] = $titolo;

        $response->getBody()->write( json_encode( $res ) );
        return $response;
    });


     // visualizza le risposta di uno specializzando a una survey X
     $groupSurveyRisposte->get('/survey/{idsurvey}/{iduser}', function (Request $request, Response $response, $args) use ($auth, $crud) {
      $db = new dataBase();
      $log = new Logger();

      $user = $request->getAttribute('user');
      $p = $request->getQueryParams();

      // se iduser = 0 visualizza le risposte dell'utente loggato
      if( $args['iduser'] == 0 ) {
        $args['iduser'] = $user['id'];
      }

      $sql = sprintf( "SELECT concat(su.nome, ' ', su.cognome) as nome_specializzando,
          DATE_FORMAT( sur.data_risposta, '%%d-%%m-%%Y') as data_risposta,
          DATE_FORMAT( sur.data_risposta, '%%H:%%i:%%s') as ora_risposta,
          sud.domanda,
          sud.idtipo_risposta,
          ss.titolo,
          sur.*
        FROM ssm_survey_risposte sur
        LEFT JOIN ssm_survey_domande sud ON sud.id=sur.iddomanda AND sud.idstatus=1
        LEFT JOIN ssm_utenti su ON su.id=sur.iduser AND su.idstatus=1
        LEFT JOIN ssm_survey ss ON ss.id=sur.idsurvey AND ss.idstatus=1
        WHERE sur.idsurvey='%s'
        AND sur.iduser='%s'",
            $db->real_escape_string( $args['idsurvey'] ),
            $db->real_escape_string( $args['iduser'] ) );
      $db->query( $sql );
      //echo $sql;

      $nDomanda = 1;
      while( $rec = $db->fetchassoc() ) {
        //print_r( $rec );

        if( $titolo == "" ) {
          $titolo = $rec['titolo'];
        }

        switch( $rec['idtipo_risposta'] ) {
          case 1: // TESTO
          case 2: // SELEZIONE SINGOLA
          case 3: // SELEZIONE MULTIPLA
            $o = json_decode( $rec['risposta'], true );
            if( is_array( $o ) ) {
              $r = implode( ", ", $o );
            } else {
              $r = $o;
            }            break;
          case 4: // SI/NO

            break;
        }


        $o = json_decode( $rec['risposta'], true );
        if( is_array( $o ) ) {
          $r = implode( ", ", $o );
        } else {
          if( $rec['idtipo_risposta'] == 4 ) {
            $r = $o == "true" ? 'Sì' : 'No';

          } else {
            $r = $o;
          }

        }


        $out['name'] = "domanda_" . $nDomanda;
        $out['required'] = false;
        $out['readonly'] = true;
        $out['type'] = "INPUT";
        $out['placeholder'] = $rec['domanda'];


        //$out['risposta'] = $rec['risposta'];
        $res['data']["domanda_" . $nDomanda] = $r;

        unset( $r );
        $res['dialogFields'][] = $out;
        $res['titolo'] = $titolo;

        $nDomanda++;

      }


      $response->getBody()->write( json_encode( $res ) );
      return $response;
  });



    $groupSurveyRisposte->get('/survey/report/{idsurvey}', function (Request $request, Response $response, $args) use ($auth, $crud) {
      $db = new dataBase();
      $log = new Logger();

      $sql = sprintf( "SELECT sd.id as iddomanda,
          domanda, risposte
          FROM ssm_survey_domande sd
          LEFT JOIN ssm_survey s ON s.id=sd.idsurvey
          WHERE sd.idsurvey='fc1c54f1-c8ce-4c97-b1c5-ab348df30444' AND sd.idstatus=1 AND s.idstatus=1");
      $db->query( $sql );
      while( $rec = $db->fetchassoc() ) {
        switch( $rec['idtipo_risposta'] ) {
          case 2:
          case 3:
            $r = json_decode( $rec['risposte'], true );
            foreach( $r as $k ) {
              $domande[$rec['iddomanda']][$k] = 0;
            }
            break;
          case 4:
            $domande[$rec['iddomanda']][0] = 0;
            $domande[$rec['iddomanda']][1] = 0;
            break;
        }

      }

     });


})->add($authMW);



?>

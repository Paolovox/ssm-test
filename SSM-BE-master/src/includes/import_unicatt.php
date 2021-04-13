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


define( 'USER_SPECIALIZZANDO', 8 );
define( 'USER_DIRETTORE_UO', 6 );
define( 'USER_DIRETTORE_SCUOLA', 5 );
define( 'USER_TUTOR', 7 );
define( 'USER_SEGRETERIA', 9 );

define( 'STATUS_OK', 1 );

define( 'ATENEO_UNICATT_ID', '7e8a03dc-4495-4804-b2da-dba60051d13b' );
define( 'USER_DOCENTE', 10 );

ini_set('max_execution_time', '2400');
ini_set('memory_limit', '-1');


function import_tutor() {
  $db = new dataBase();
  $db2 = new dataBase();

  $Utils = new Utils();

  echo "<br>Tutor...";
  //return;

  $sql = sprintf( "SELECT
      an.CF_UTENTE as id,
      ar.ID_RUOLO,
      an.NOME_UTENTE as nome,
      an.COGNOME_UTENTE as cognome,
      an.GENERE_UTENTE as genere,
      if( an.MAIL_AZIENDALE_UTENTE != '', MAIL_AZIENDALE_UTENTE, MAIL_ALTERNATIVA_UTENTE ) as email,
      an.CF_UTENTE as codice_fiscale,
      an.TELEFONO_UTENTE as telefono,
      an.LUOGO_NASCITA_UTENTE as luogo_nascita,
      an.DATA_NASCITA_UTENTE as data_nascita,
      an.MATRICOLA_UTENTE as matricola,
      an.INDIRIZZO_VIA_UTENTE as residenza_indirizzo,
      an.INDIRIZZO_CITTA_UTENTE as residenza_citta,
      an.INDIRIZZO_CAP_UTENTE as residenza_cap
    FROM ssm_unicatt_import_scuole.im_ruolo_utente ar
    LEFT JOIN ssm_unicatt_import_scuole.im_anagrafica an ON an.ID_UTENTE=ar.ID_UTENTE
    LEFT JOIN ssm_unicatt.ssm_utenti u ON u.id=an.CF_UTENTE
    WHERE ar.ID_RUOLO IN (2, 3) AND u.id is null
    ORDER BY an.COGNOME_UTENTE, an.NOME_UTENTE
    LIMIT 0,500
    ");
  $db->query($sql);
  if( $db->error() != "" ) {
    echo $db->error();
    exit;
  }


  while ($rec = $db->fetchassoc()) {
      echo "<br>" . $rec['COD_FISCALE'] . ' - ' . $rec['cognome'] . ' ' . $rec['nome'] . ' ' . $rec['email'];

      $idruolo = $rec['ID_RUOLO'];

      unset( $rec['ID_RUOLO'] );

      /*
      switch( $idruolo ) {
        case 2: // TUTOR
          $rec['id'] = "T-" . $rec['id'];
          break;
        case 3: // DOCENTE
          $rec['id'] = "D-" . $rec['id'];
          break;
      }
      */

      $rec['idstatus'] = 1;
      $rec['date_create'] = "now()";
      $rec['date_update'] = "now()";

      $ret = $Utils->dbSql(true, "ssm_unicatt.ssm_utenti", $rec, "", "");
      if ($ret['success'] != 1) {
          echo "<br>Errore:" . $db->error();
          print_r($ret);
          exit;
      }


      ruoli_utente_delete( $db2, $rec['id'] );


      // tutor vuoto
      $tutor[] = "";

      foreach ($tutor as $k => $v) {
          $ins_ruolo = array(
              "idutente" => $rec['id'],
              "idruolo" => $idruolo == 2 ? USER_TUTOR : USER_DOCENTE,
              "idateneo" => ATENEO_UNICATT_ID,
              "idscuola" => "",
              "idpresidio" => $v['idpresidio'],
              "idunita" => $v['idunita'],
              "idstatus" => STATUS_OK,
              "date_create" => "now()",
              "date_update" => "now()"
          );

          $ret = $Utils->dbSql(true, "ssm_unicatt.ssm_utenti_ruoli_lista", $ins_ruolo, "", "", false);
          if ($ret['success'] != 1) {
              print_r($ret);
              echo "<br>" . $db->error();
              exit;
          }
      }

  }

  echo "<br>Fine insert tutor";



}



function import_studenti() {

  echo "<br>Aggiornamento studenti...<br>";
  //echo "<br>OK1" . $_SERVER['HTTP_X_FORWARDED_FOR'];

  $db = new dataBase();
  $db2 = new dataBase();

  $Utils = new Utils();

  $sql = sprintf("SELECT
      sp.ID_UTENTE,
      concat( 'S-', an.MATRICOLA_UTENTE ) as id,
  	  sp.ID_SSM as id_ssm,
      an.COGNOME_UTENTE as cognome,
      an.NOME_UTENTE as nome,
      an.CF_UTENTE as email,
      an.GENERE_UTENTE as genere,
      an.MATRICOLA_UTENTE as matricola,
      an.CF_UTENTE as codice_fiscale,
      an.INDIRIZZO_VIA_UTENTE as residenza_indirizzo,
      an.INDIRIZZO_CITTA_UTENTE as residenza_citta,
      an.INDIRIZZO_CAP_UTENTE as residenza_cap,
      an.TELEFONO_UTENTE as telefono,
      an.DATA_NASCITA_UTENTE as data_nascita,
      an.LUOGO_NASCITA_UTENTE as luogo_nascita,
      sp.COORTE_UTENTE as idcoorte,
      sp.ANNO_DI_CORSO_UTENTE as anno_scuola
    FROM ssm_unicatt_import_scuole.im_specializzando_1920 sp
    LEFT JOIN ssm_unicatt_import_scuole.im_anagrafica_1920 an ON an.MATRICOLA_UTENTE=sp.MATRICOLA_UTENTE
    WHERE COORTE_UTENTE IN ('2019')
    ORDER BY an.MATRICOLA_UTENTE" );
  $db->query($sql);
  echo $sql;
  echo $db->error();


  while ($rec = $db->fetchassoc()) {
      if( ++$n == 1 )
        print_r( $rec );

      echo "<br>" . $rec['cognome'] . ' ' . $rec['nome'] . ' - ' . $rec['email'];
      if( $rec['matricola'] == "" ) {
        echo "<br><b>MATRICOLA NON ESISTENTE!: " . $rec['ID_UTENTE'] . "</b>";
      }

      unset( $rec['ID_UTENTE'] );

      $cod_scuola = $rec['id_ssm'];
      $idscuola = idscuola_get( $db2, $cod_scuola, ATENEO_UNICATT_ID );

      $coorte = sprintf( "%s/%s", $rec['idcoorte'], $rec['idcoorte'] + 1 );
      $idcoorte = id_coorte_get( $db2, $idscuola['id'], $coorte );

      if( $idcoorte == "" ) {
        $notfound[$cod_scuola] = $idscuola['nome'] . ' - ' . $coorte;
        echo "<br><b>Coorte non trovata</b> - idscuola: " . $idscuola['id'] . " - " . $rec['idcoorte'];
      } else {
        $rec['idcoorte'] = $idcoorte . ' - ' . $idscuola['id'] . ' - ' . $idscuola['nome'];
      }

      $rec['idstatus'] = 1;
      $rec['date_create'] = 'now()';
      $rec['date_update'] = 'now()';
      $rec['password'] = 'NOAUTH';

      unset( $rec['id_ssm'] );
      print_r( $rec );


      $ret = $Utils->dbSql(true, "ssm_unicatt.ssm_utenti", $rec, "", "");
      if ($ret['success'] != 1) {
          echo $db->error();
          print_r($ret);
          exit;
      }


      ruoli_utente_delete( $db2, $rec['id'] );

      $ins_ruolo = array(
          "idutente" => $rec['id'],
          "idruolo" => USER_SPECIALIZZANDO,
          "idateneo" => ATENEO_UNICATT_ID,
          "idscuola" => $idscuola['id'],
          "idpresidio" => "",
          "idunita" => "",
          "idstatus" => STATUS_OK,
          "date_create" => "now()",
          "date_update" => "now()"
      );


      echo "<br>Inserimento ruolo " . json_encode( $ins_ruolo );

      $ret = $Utils->dbSql(true, "ssm_unicatt.ssm_utenti_ruoli_lista", $ins_ruolo, "", "");
      echo " - success: " . $ret['success'];

      if ($ret['success'] != 1) {
          print_r($ret);
          exit;
      }


  }


  echo "<br>fine insert studenti";

  echo '<br>Coorti non trovate:';
  foreach( $notfound as $k => $v ) {
    echo '<br>' . $k . ' - ' . $v;
  }

  print_r( $notfound );
}



function id_coorte_get( $db, $idscuola, $coorte ) {
  //$db = new dataBase();
  $sql = sprintf( "SELECT id
      FROM ssm.ssm_pds_coorti
      WHERE idscuola_specializzazione='%s' AND nome='%s' AND idstatus=1",
    $db->real_escape_string( $idscuola ), $db->real_escape_string( $coorte ) );
  echo "<br><b>COORTE</b>: " . $sql;
  $db->query( $sql );
  $rec = $db->fetchassoc();
  return $rec['id'];
}



function pw_reset() {

    $db = new dataBase();
    $Utils = new Utils();

    $hashedPassword = password_hash( "g9ckCjje", PASSWORD_DEFAULT);
    $sql = sprintf( "UPDATE ssm_unicatt.ssm_utenti SET password='%s' WHERE password='NOAUTH'", $db->real_escape_string( $hashedPassword ) );
    $db->query( $sql );

}




function idscuola_get( $db, $cod_scuola, $idateneo ) {
  $db = new dataBase();

  $sql = sprintf( "SELECT
      sat.id, s.NOME_SDS as nome
    FROM
      ssm.ssm_associazioni_scuole sa
      LEFT JOIN ssm_unicatt_import_scuole.im_scuole s ON s.ID_SDS = sa.idscuola_ext
      LEFT JOIN ssm.ssm_scuole_atenei sat ON sat.idscuola=sa.idscuola AND sat.idateneo='%s' AND sat.idstatus=1
    WHERE
	    sa.idscuola_ext = '%s'", $db->real_escape_string( $idateneo ),  $db->real_escape_string( $cod_scuola ) );
  echo '<br>' . $sql;
  $db->query( $sql );
  if( $db->error() != "" ) {
    echo $db->error();
    exit;
  }
  $rec = $db->fetchassoc();

  return $rec;
}




function ruoli_utente_delete( $db2, $id ) {
  //$db = new dataBase();
  $sql = sprintf( "DELETE FROM ssm_unicatt.ssm_utenti_ruoli_lista WHERE idutente='%s'", $id );
  echo "<br>" . $sql;
  $db2->query( $sql );
  echo "<br>" . $db2->error();
  return;
}



?>

<?php

require 'vendor/autoload.php';

use \ottimis\phplibs\Logger;
use \ottimis\phplibs\Utils;
use \ottimis\phplibs\dataBase;
use \ottimis\phplibs\UUID;

// error_reporting( 0 );

class CRUD {

  private $data;

  function __construct( $data ) {
    $this->data = $data;
    /*
      $data = {
        "table": "uz_condomini",
        "id": "id",
        "sort": "nome_condominio",
        "order": "asc"
        "status_field": "idstatus",
        "update_field": "date_update",
        "create_field": "date_create"

      }
    */

  }

  function record_list( $req ) {
    $db = new dataBase();
    $utils = new Utils();
    $log = new Logger();

    $req['count'] = $req['c'] != "" ? ($req['c']) : 20;
    $req['start'] = $req['p'] != "" ? ($req['p']-1) * $req['count'] : 0;
    $req['sort'] = $req['srt'] != "" ? $req['srt'] : $this->data['sort'];
    $req['order'] = $req['o'] != "" ? $req['o'] : $this->data['order'];

    /*
    "where" => [
        [
        "field" => "idamministratore",
        "operator" => "!=",
        "value" => "''",
        ]
      ],
    */

    $where = array();
    foreach( $req as $k => $v ) {
      //$log->log( $k . " -> " . $v );
      if( substr( $k, 0, 1) == "_" ) {
        $where[] = array(
          "field" => substr($k, 1),
          "operator" => "=",
          "value" => $v,
          "operatorAfter" => " AND "
        );
      } else if ( substr( $k, 0, 1) == ">" )  {
        $where[] = array(
          "field" => substr($k, 1),
          "operator" => ">",
          "value" => $v,
          "operatorAfter" => " AND "
        );
      }
    }
    unset( $where[sizeof($where)]['operatorAnd'] );

    if( $req['search'] ) {
      $where[] = $req['search'];
      unset( $req['search'] );
    }

    if( $req['multi_search'] ) {
      $req['multi_search'][0]['field'] = "( " . $req['multi_search'][0]['field'];
      $last = $req['multi_search'][count($req['multi_search']) - 1];
      $req['multi_search'][count($req['multi_search']) - 1]['custom'] = $last['field'] . " " . $last['operator'] . " '" . $last['value'] . "' )";
      $where = array_merge($where, $req['multi_search']);
      unset( $req['multi_search'] );
    }

    if( isset( $req['where'] ) )
      $where = $req['where'];

    if( isset( $req['customWhere'] ) )
      $where[] = $req['customWhere'];

    $options = [
      "log" => isset( $this->data['log']) ? $this->data['log'] : false,
      "count" => true,
      "select" => ["*"],
      "from" => $this->data['table_join'] != "" ? $this->data['table_join']:$this->data['table'],
      "where" => $where,
      "order" => $req['sort'] . " " . $req['order'],
      "limit" => [$req['start'], $req['count']]
    ];

    if( $this->data['list_fields'] )
      $options['select'] = $this->data['list_fields'];
    if( $this->data['list_join'] )
      $options['join'] = $this->data['list_join'];

    $log->log( "record_list: " . json_encode( $options ) );


    $res = $utils->dbSelect( $options );

    return $res;
  }

  function record_get( $id ) {
    $db = new dataBase();
    $utils = new Utils();

    $res = $utils->dbSelect( [
      "select" => ["*"],
      "from" => $this->data['table'],
      "where" => [
        [
        "field" => $this->data['id'],
        "operator" => "=",
        "value" => $id,
        ]
      ],
    ]);

    return $res;
  }

  function record_new( $p ) {
    $Utils = new Utils();
    $uuid = new UUID();
    $log = new Logger();

    if( $this->data['create_field'] != '' )
      $p[$this->data['create_field']] = "now()";
    if( $this->data['update_field'] != '' )
      $p[$this->data['update_field']] = "now()";
    if( $this->data['status_field'] != '' )
      $p[$this->data['status_field']] = "1";

    $p[$this->data['id']] = $uuid->v4();

    $log->log( "NEW " . $uuid->v4() );
    $log->log( "NEW " . json_encode( $p ) );

    $res = $Utils->dbSql( true, $this->data['table'], $p, "", "" );
    $res['id'] = $p[$this->data['id']];

    return $res;

  }


  function record_update( $id, $p ) {
    $Utils = new Utils();

    if( $this->data['update_field'] != '' )
      $p[$this->data['update_field']] = "now()";

    $res = $Utils->dbSql( false, $this->data['table'], $p, $this->data['id'], $id );
    return $res;


  }


  function record_delete( $id ) {
    $Utils = new Utils();

    if( $this->data['update_field'] != '' )
      $p[$this->data['update_field']] = "now()";
    if( $this->data['status_field'] != '' )
      $p[$this->data['status_field']] = "2";

    $res = $Utils->dbSql( false, $this->data['table'], $p, $this->data['id'], $id );
    return $res;
  }




}

?>

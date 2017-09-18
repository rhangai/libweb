<?php
namespace LibWeb\model;

use gossi\codegen\model\PhpClass;
use gossi\codegen\model\PhpMethod;
use gossi\codegen\model\PhpParameter;
use gossi\codegen\model\PhpConstant;
use gossi\codegen\generator\CodeGenerator;

class Generator {

	public static function arrayToString( $array, $ident = 0 ) {
		foreach ( $array as &$item ) {
			if ( is_array( $item ) )
				$item = self::arrayToString( $item, $ident + 1 );
			else if ( $ident > 0 )
				$item = str_repeat( "\t", $ident ).$item;
		}
		return implode( "\n", $array );
	}
	
	public static function generateCreate( $class, $model ) {
		$body = array();

		$validator = array();
		foreach ( $model->fields as $field ) {
			$validator[] = '"oi" => v::s(),';
		}
		$body[] = '$data = v::validate( $data, array(';
		$body[] = $validator;
		$body[] = ') );';
		
		$create = PhpMethod::create( 'create' )
			->setStatic( true )
			->addParameter( PhpParameter::create( 'data' ) )
			->setBody(
				self::arrayToString( $body )
			);
		$class->setMethod( $create );
	}
	/**
	 * Lista todos os 
	 */
	public static function generateList( $class, $model ) {
		$list = PhpMethod::create( 'listAll' )
			->setStatic( true )
			->setBody(
				'return DB::fetchAll( "SELECT * FROM '.$model->table.'" );'
			)->setDescription( "List all items from ".$model->table );
		$class->setMethod( $list );
	}
	/**
	 * 
	 */
	public static function generate( $model ) {
		if ( !$model instanceof Model )
			$model = new Model( $model );
		
		$class = new PhpClass( $model->name );
		$class
			->setConstant( new PhpConstant('TABLE', $model->table ) );
		
		self::generateCreate( $class, $model );
		self::generateList( $class, $model );
		
		$generator = new CodeGenerator();
		return (object) array(
			"class" => $generator->generate( $class )
		);
	}
	/**
	 *
	 */
	public static function generateFromYaml( $base, $input, $output ) {
		$files = \Webmozart\Glob\Glob::glob( $input."/**/*.yaml" );
	}
};

require_once dirname(dirname(__DIR__)).'/vendor/autoload.php';

Generator::generateFromYaml( ".", __DIR__, "" );
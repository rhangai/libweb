<?php namespace LibWeb\Util;

class DauxProcessorHelper {
	
    public static function generateAPI( $builderDir, $base, $path = array() ) {
		if ( !class_exists("\PhpParser\ParserFactory" ) )
			throw new \Exception( "Please install php-parser from nikic/php-parser" );
		if ( !class_exists("\Todaymade\Daux\Tree\Builder" ) )
			throw new \Exception( "Usable only from daux preprocessor" );
		
		$fulldir = implode( '/', array_filter( array_merge( array( $base ), $path ) ) );
		$files   = scandir( $fulldir );
		foreach ( $files as $file ) {
			if ( $file == '.' || $file == '..' )
				continue;

			self::generateAPIFile( $builderDir, $base, $path, $file );
		};
	}


	private static function generateAPIFile( $builderDir, $base, $path, $file ) {
		$fullfile = implode( '/', array_filter( array_merge( array( $base ), $path, array( $file ) ) ) );
		$content  = file_get_contents( $fullfile );
		
		$parser = (new \PhpParser\ParserFactory)->create( \PhpParser\ParserFactory::PREFER_PHP7 );
		$stmts  = $parser->parse( $content );

		foreach ( $stmts as $stmt ) {
			if ( $stmt instanceof \PhpParser\Node\Stmt\ClassLike ) {
				$filebase  = pathinfo($file, PATHINFO_FILENAME);

				$apiPath = implode("/", array_filter( array_merge( $path, array( strtolower( $filebase ) ) ) ) );
				if ( $apiPath )
					$apiPath = "/".$apiPath;
				$page = \Todaymade\Daux\Tree\Builder::getOrCreatePage( $builderDir, strtolower( $filebase ) );
				$page->setContent( self::generateAPIClassMarkdown( $stmt, $apiPath ) );
			}
		};
	}
	
	private static function generateAPIClassMarkdown( $classStmt, $baseAPI ) {
		$content = array();
		$API = new \LibWeb\API( "/api" );
		$prettyPrinter = new \PhpParser\PrettyPrinter\Standard();

		foreach ( $classStmt->stmts as $stmt ) {
			if ( $stmt instanceof \PhpParser\Node\Stmt\ClassMethod ) {

				$name = $stmt->name;

				if ( preg_match( '/^(GET|POST|REQUEST)_(\w+)$/', $name, $matches ) ) {
					$method  = $matches[1];
					$name    = $matches[2];
					$apiPath = $API->nameToPath( $name );
					
					$content[] = "\n".$method." ".$baseAPI.$apiPath."\n--------------\n\n";
					$comment = $stmt->getDocComment();
					if ( $comment )
						$comment = self::generateStripComment( $comment->getText() );

					
					$content[] = $comment ?: "TODO: Gerar doc";
					$content[] = "```php\n".$prettyPrinter->PrettyPrint( $stmt->stmts )."\n```";
				}
			}
		}
		return implode( "\n", $content );
	}

	private static function generateStripComment( $comment ) {
		if ( substr( $comment, 0, 2 ) === '//' )
			return substr( $comment, 2 );

		$options  = (object)array(
			"params" => array(),
			"ret"    => null
		);
		$comment = trim( substr( $comment, 2, -2 ), "*" );
		$lines   = array_map( function( $line ) use( &$options ) {
		    $line = trim( $line );
			if (!$line)
				return "";
			if ( $line[0] === '*' )
				$line = substr( $line, 1 );
			
			if ( preg_match( "/^\s*@param\s+(\w+)/", $line, $matches ) ) {
				$desc = trim( substr( $line, strlen( $matches[0] ) ) );
				if ( $desc && ($desc[0] === '-') )
					$desc = trim( substr($desc, 1) );
				$options->params[ $matches[1] ] = $desc;
				return null;
			}
			if ( preg_match( "/^\s*@returns?\s+/", $line, $matches ) ) {
				$desc = trim( substr( $line, strlen( $matches[0] ) ) );
				$options->ret = $desc;
				return null;
			}
		    return $line;
		}, explode( "\n", $comment ) );

		$lines = array_values( array_filter( $lines, function( $line ) {
			return $line !== null;
		}) );

		for ( $i = 0; $i < count($lines) - 1; ++$i ) {
			if ( trim($lines[$i]) != "" )
				break;
		}
		for ( $j = count($lines) - 1; $j>=0; --$j ) {
			if ( trim($lines[$j]) != "" )
				break;
		}

		$start = $i;
		$len   = ($j-$start+1);
		if ( $len <= 0 )
			$lines = array();
		else
			$lines = array_slice( $lines, $start, $len );
		


		$prepend = '';
		if ( $options->params ) {
			$mapped = array();
			foreach ( $options->params as $key => $value ) {
				$mapped[] = "     - **".$key."**: ".$value;
			};
			$prepend .= "- *Parâmetros*\n".implode("\n", $mapped)."\n\n";
		}
		if ( $options->ret ) {
			$prepend .= "- *Retorno*\n\n  ".$options->ret."\n\n";
		}
		if ( $prepend ) {
			return $prepend."- *Descrição*\n\n  ".implode( "\n  ", $lines );
		}
		return trim( $prepend.implode( "\n", $lines ) );
	}
}

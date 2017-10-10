<?php
namespace LibWeb\debug;

/**
 * DebugExeption collector
 */
class ExceptionsCollector extends \DebugBar\DataCollector\ExceptionsCollector {
	
	public function formatThrowableData($e)
	{
		$fileinfo = is_callable( array( $e, "serializeFile" ) ) ? $e->serializeFile() : array( "file" => $e->getFile(), "line" => $e->getLine() );
		$filePath = $fileinfo["file"];
		$line     = $fileinfo["line"];
		if ($filePath && file_exists($filePath)) {
			$lines = file($filePath);
			$start = $line - 4;
			$lines = array_slice($lines, $start < 0 ? 0 : $start, 7);
		} else {
			$lines = array("Cannot open the file ($filePath) in which the exception occurred ");
		}
		return array(
			'type' => get_class($e),
			'message' => $e->getMessage(),
			'code' => $e->getCode(),
			'file' => $filePath,
			'line' => $line,
			'surrounding_lines' => $lines
		);
	}
};
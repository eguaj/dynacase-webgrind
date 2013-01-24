<?php

include_once ('../../../WHAT/Lib.Prefix.php');

$htAccess = '../../../.htaccess';
$traceDir = join(DIRECTORY_SEPARATOR, array(DEFAULT_PUBDIR, 'admin', 'service.d', 'Webgrind', 'traces'));

$xdebugIsLoaded = extension_loaded('xdebug');

$defaultConfig = array(
	'xdebug.profiler_enable_trigger' => 1,
	'xdebug.profiler_enable' => 0,
	'xdebug.profiler_output_dir' => $traceDir
);

$currentConfig = array();
foreach ($defaultConfig as $k => $v) {
	$currentConfig[$k] = ini_get($k);
}

$error = '';

try {
	if ($xdebugIsLoaded) {
		if (isset($_REQUEST['enable_profiling'])) {
			enable_profiling($htAccess, $defaultConfig);
			reload();
		} else if (isset($_REQUEST['disable_profiling'])) {
			disable_profiling($htAccess, $defaultConfig);
			reload();
		} else if (isset($_REQUEST['delete_trace']) && isset($_REQUEST['trace'])) {
			delete_trace($traceDir, $_REQUEST['trace']);
			reload();
		} else if (isset($_REQUEST['download_trace']) && isset($_REQUEST['trace'])) {
			download_trace($traceDir, $_REQUEST['trace']);
		}
	}
} catch (Exception $e) {
	$error = $e->getMessage();
	error_log(__FILE__ . " " . $error);
}

$traceList = getTraceList($traceDir);

function enable_profiling($htAccess, $config) {
	$htIn = file($htAccess);
	if ($htIn === false) {
		throw new Exception(sprintf("Error reading from '%s'.", $htAccess));
	}
	$htOut = array_filter($htIn, function($l) use ($config) {
		foreach ($config as $k => $v) {
			if (preg_match('/^\s*php_value\s+' . preg_quote($k) . '/', $l)) {
				return false;
			}
		}
		return true;
	});
	foreach ($config as $k => $v) {
		$htOut []= sprintf('php_value %s "%s"', $k, $v) . PHP_EOL;
	}
	$ret = file_put_contents($htAccess, join('', $htOut));
	if ($ret === false) {
		throw new Exception(sprintf("Error writing to '%s'.", $htAccess));
	}
}

function disable_profiling($htAccess, $config) {
	$htIn = file($htAccess);
	if ($htIn === false) {
		throw new Exception(sprintf("Error reading from '%s'.", $htAccess));
	}
	$htOut = array_filter($htIn, function($l) use ($config) {
		foreach ($config as $k => $v) {
			if (preg_match('/^\s*php_value\s+' . preg_quote($k) . '/', $l)) {
				return false;
			}
		}
		return true;
	});
	if (count($htOut) !== count($htIn)) {
		$ret = file_put_contents($htAccess, join('', $htOut));
		if ($ret === false) {
			throw new Exception(sprintf("Error writing to '%s'.", $htAccess));
		}
	}
}

function delete_trace($traceDir, $trace) {
	if (strpos($trace, '/') !== false || substr($trace, 0, 1) == '.') {
		throw new Exception(sprintf("Invalid trace name '%s'.", $trace));
	}
	$tracePath = $traceDir . DIRECTORY_SEPARATOR . $trace;
	if (is_file($tracePath)) {
		$ret = unlink($tracePath);
		if ($ret === false) {
			throw new Exception(sprintf("Error deleting trace '%s'."));
		}
	}
}

function download_trace($traceDir, $trace) {
	if (strpos($trace, '/') !== false) {
		throw new Exception(sprintf("Invalid trace name '%s'.", $trace));
	}
	$tracePath = $traceDir . DIRECTORY_SEPARATOR . $trace;
	if (is_file($tracePath)) {
		$location = join(DIRECTORY_SEPARATOR, array(dirname($_SERVER['PHP_SELF']), 'traces', $trace));
		header('Location: ' . $location);
		exit(0);
	}
}

function getTraceList($traceDir) {
	$list = array_filter(scandir($traceDir), function ($f) use ($traceDir) {
		return (is_file($traceDir . DIRECTORY_SEPARATOR . $f) && substr($f, 0, 1) != '.');
	});
	return $list;
}

function reload() {
	header('Location: ' . $_SERVER['PHP_SELF']);
	exit(0);
}

function p($s) {
	print($s);
}

function e($s) {
	return htmlspecialchars($s);
}

function pe($s) {
	p(e($s));
}

?>
<html>
<head>
<title>Webgrind</title>
<style>
.true {
	color: green;
}
.false {
	color: red;
}
.error_message {
	color: red;
}
</style>
</head>
<body>

<div class="xdebug_loaded">
Xdebug extension is
<?php
	p($xdebugIsLoaded?'<span class="true">loaded</span>':'<span class="false">not loaded</span>')
?>
.
</div>

<?php
	if($xdebugIsLoaded) {
?>

<div class="php_ini">
<p>Xdebug profiling PHP INI configuration:</p>
<pre>
<?php
	foreach ($currentConfig as $k => $v) {
		p(e($k) . ' = "' . e($v) .'"' . PHP_EOL);
	}
?>
</pre>
</div>

<div class="button_bar">
[<a href="?enable_profiling">Enable profiling</a>] [<a href="?disable_profiling">Disable profiling</a>] [<a href="webgrind">Run webgrind</a>]
</div>

<div class="error_message">
<pre>
<?php
	pe($error)
?>
</pre>
</div>

<div class="trace_list">
<p><a href="traces/">Traces</a>:</p>
<ul>
<?php
	foreach ($traceList as $f) {
?>
<li>[<a href="?delete_trace&trace=<?php pe($f) ?>">delete</a>] [<a href="?download_trace&trace=<?php pe($f) ?>">download</a>] <?php pe($f) ?></li>
<?php
	}
?>
</ul>
</div>

<?php
	} // xdebugIsLoaded
?>

</body>

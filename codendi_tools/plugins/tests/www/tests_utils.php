<?php
error_reporting(E_ALL);
require(getenv('CODENDI_LOCAL_INC')?getenv('CODENDI_LOCAL_INC'):'/etc/codendi/conf/local.inc');
require($GLOBALS['db_config_file']);

require_once('../include/simpletest/unit_tester.php');
require_once('../include/simpletest/mock_objects.php');
require_once('../include/simpletest/web_tester.php');
require_once('../include/simpletest/expectation.php');

require_once('CodendiReporter.class.php');

// It seems that runkit doesn't work properly on x86_64, at least with
// PHP 5.1.6
if (PHP_INT_SIZE == 4) {
    if (extension_loaded('runkit')) {
        require_once(dirname(__FILE__) .'/../include/simpletest/mock_functions.php');
        define('MOCKFUNCTION_AVAILABLE', true);
    } else {
        define('MOCKFUNCTION_AVAILABLE', false);
    }
} else {
    define('MOCKFUNCTION_AVAILABLE', false);
}


$GLOBALS['config']['plugins_root'] = $GLOBALS['sys_pluginsroot'];
$GLOBALS['config']['tests_root']   = '/tests/';
$GLOBALS['config']['excludes']     = array('.', '..', '.svn');
$GLOBALS['config']['suffix']       = 'Test.php';

$GLOBALS['tests']                  = array();

function clean_plugins_root($entry) {
    return substr($entry, strlen($GLOBALS['config']['plugins_root']), -strlen($GLOBALS['config']['tests_root']));
}
function search_tests_rec($dir, &$tab, $entry) {
    if (is_dir($dir)) {
        if ($dh = opendir($dir)) {
            while (($file = readdir($dh)) !== false) {
                if (!in_array($file, $GLOBALS['config']['excludes']) && $file[0] !== '_') {
                    if (is_dir("$dir/$file")) {
                        search_tests_rec("$dir/$file", $tab[($entry == 'tests'?'Codendi':$entry)], $file);
                    } else if(substr($file, -strlen($GLOBALS['config']['suffix'])) === $GLOBALS['config']['suffix']) {
                        $tab[($entry == 'tests'?'Codendi':$entry)]['_tests'][] = $file;
                    }
                }
            }
            closedir($dh);
        }
    }
}
function search_tests($entry) {
    search_tests_rec($GLOBALS['config']['plugins_root'] . $entry . $GLOBALS['config']['tests_root'], $GLOBALS['tests'], $entry);
    return $GLOBALS['tests'];
}
$roots = glob($GLOBALS['config']['plugins_root'] .'*'. $GLOBALS['config']['tests_root']);
$roots=array_map('clean_plugins_root', $roots);
$roots=array_map('search_tests', $roots);

//{{{ Tri
function sort_by_key_and_by_value_depending_of_type($a, $b) {
    return strnatcasecmp((is_array($a) ? key($a) : $a), (is_array($b) ? key($b) : $b));
}
function sort_tests(&$entry, $key) {
    if ($key == '_tests') {
        usort($entry, 'strnatcasecmp');
    } else {
        uksort($entry, 'strnatcasecmp');
        array_walk($entry, 'sort_tests');
    }
}
uksort($GLOBALS['tests'], 'strnatcasecmp');
array_walk($GLOBALS['tests'], 'sort_tests');
//}}}

function &get_group_tests($tablo) {
    $g =& new GroupTest("All Tests");
    foreach($tablo as $plugin => $tests) {
        $o =& new GroupTest($plugin .' Tests');
        foreach($tests as $c => $t) {
            add_test_to_group($t, $c, 
                array(
                'group' => &$o, 
                'path' => $GLOBALS['config']['plugins_root'] . ($plugin == 'Codendi' ? 'tests' : $plugin) . $GLOBALS['config']['tests_root']
            ));
        }
        $g->addTestCase($o);
    }
    return $g;
}
?>
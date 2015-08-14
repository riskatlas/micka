<?php
/**
 * Metainformation catalogue
 * --------------------------------------------------
 *
 * HARVEST for MicKa
 *
 * @link       http://www.bnhelp.cz
 * @package    Micka admin
 * @category   Metadata
 * @version    20140522
 */

function adminHarvest($harvestAction) {
    $params = array();
    while(list($key,$val) = each($_REQUEST)) {
        $params[$key] = htmlspecialchars($val);
    }
    $harvest = new Harvest(null, null);
    $rs = array();
    $rs['types'] = array_flip($harvest->types);
    $rs['error'] = '';
    //my_print_r($params); my_print_r($harvestAction); //exit;
    switch ($harvestAction) {
        case 'save':
            $overwrite = isset($params['new']) && $params['new'] == 1 ? false : true;
            if($params['handlers']) {
                $handlers = "email:" . $params['handlers'];
            }
            if($params['period'] <= 0) $params['period'] = "0";
            if($params['ok']) {
                if(!canAction('*')) $params['active'] = 0;
                $result = $harvest->setParameters(
                    $params['id'],
                    $params['source'], 
                    $params['type'], 
                    $handlers, 
                    "P".$params['period']."D",
                    $params['filter'],
                    $params['active'],
                    $overwrite
                );
                if($result['status'] == 'fail') {
                    $rs['error'] = $result['error'];
                }
            }
            break;
        case 'edit':
            if($params['id'] && $result['status'] != 'fail') {
                $par = $harvest->getParameters($params['id']);
                $params = $par[0];
                $days = $par[0]['h_interval']/24;
                $params['period'] = $days;
                $email = explode(":",$params['handlers']);
                $params['handlers'] = $email[1];
            } else {
                $rs['new'] = '1';
            }
            $rs['values'] = $params;
            $rs['isadmin'] = canAction('*');
            return $rs;
        case 'delete':
            $harvest->delete($params['id']);
            break;	
        default:
            break;	
    }

    $rs['list'] = $harvest->getParameters();
    //my_print_r($rs); exit;
    return $rs;
    
}

<?php
    require_once 'core/generics/Param.php';
    require_once 'core/generics/Controller.php';
    require_once 'core/generics/GenericDao.php';        
?>
<?    
    $type = $_GET['type'];
    $id = $_GET['id'];
    if($type == 'Groups')
        require_once 'core/generics/datacenter/Group.php';
    else
        if($type == 'origin' || $type == 'destiny'){
            require_once 'core/generics/datacenter/Country.php';
        }else
            require_once 'core/generics/datacenter/'.$type.'.php';
    
    $dao = new GenericDao(Connection::connect());    
    $controller = new Controller($dao);
    header('Content-type: application/json');
    echo $controller->getTypeToDatacenter(strtolower($type), $id);
?>
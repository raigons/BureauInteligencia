<?php

/**
 * Description of DatacenterController
 *
 * @author Ramon
 */
class DatacenterController {

    /**
     * @var DatacenterRepository 
     */
    private $datacenterRepository;
    
    /**
     * @var DatacenterService 
     */
    private $datacenterService;
    
    /**
     * @var Statistic 
     */
    private $statistic;
    
    /**
     *
     * @var JsonResponse 
     */
    private $jsonResponse;
    
    /**
     *
     * @var DataGrouper 
     */
    private $grouper;
    
    /**     
     * @var BuilderFactory 
     */
    private $builderFactory;
    
    private $asJson = false;
    
    private $chartType;
    
    public function DatacenterController(DatacenterService $service, Statistic $statistic, 
            JsonResponse $jsonResponse, DataGrouper $grouper, BuilderFactory $factory){
        $this->datacenterService = $service;
        $this->statistic = $statistic;
        $this->jsonResponse = $jsonResponse;
        $this->grouper = $grouper;        
        $this->builderFactory = $factory;
    }
    
    private function getBuilder($type) {
        return $this->builderFactory->getBuilder($type);
    }
    
    public function getValuesAsJson(){
        $this->asJson = true;
    }
    
    //GET ://datacenter/chart
    public function getChart($subgroup, $font, $type, $variety, $origin, $destiny, array $years){
        $xmlChart = $this->buildChart($subgroup, $font, $type, $variety, $origin, $destiny, $years);
        return $this->buildChartJsonResponse($xmlChart);
    }
    
    private function buildChartJsonResponse($xmlChart){
        $xmlChart = str_replace("\"", "'", trim($xmlChart));
        $this->jsonResponse->response(true, null)->addValue("chart", trim($xmlChart));
        $this->jsonResponse->addValue("typeChart", $this->chartType);
        return $this->jsonResponse->withoutHeader()->serialize();        
    }
    
    public function getDistinctGroupChart($g1, $g2, array $years){
        $xmlChart = $this->buildChartSearchingDistinctGroups($g1, $g2, $years);
        return $this->buildChartJsonResponse($xmlChart);
    }
    
    //GET ://datacenter/table
    public function getTable($subgroup, $font, $type, $variety, $origin, $destiny, array $years){
        $jsonTable = $this->buildTableAsJson($subgroup, $font, $type, $variety, $origin, $destiny, $years);
        return $this->buildTableJsonResponse($jsonTable);                
    }

    private function buildTableJsonResponse($jsonTable){
        $jsonTable = utf8_encode($jsonTable);
        $jsonTable = json_decode($jsonTable);
        return $this->jsonResponse->response(true, null)->addValue("tabela",$jsonTable)->withoutHeader()->serialize();        
    }
    
    public function getDistinctGroupsTable($g1,$g2,array $years){
        $jsonTable = $this->buildTableSearchingDistinctGroups($g1, $g2, $years);
        return $this->buildTableJsonResponse($jsonTable);
    }
    
    //GET ://datacenter/spreadsheet
    public function getExcelTable($subgroup, $font, $type, $variety, $origin, $destiny, array $years){
        $spreadsheetName = $this->buildExcelTable($subgroup, $font, $type, $variety, $origin, $destiny, $years);
        $path = LinkController::getBaseURL() . "/" . $spreadsheetName;
        return $this->jsonResponse->response(true, null)
                                  ->addValue("planilha",$path)
                                  //->addValue("asHtml", $this->buildExcelHTML($spreadsheetName))
                                  ->withoutHeader()->serialize();
    }    
    
    private function buildExcelHTML($spreadsheetFile){        
        $data = new Spreadsheet_Excel_Reader($spreadsheetFile);
        return $data->dump(true, true);
    }
    
    // table statistics
    public function getStatisticTable($subgroup, $font, $type, $variety, $origin, $destiny, array $years){
        $jsonTable = $this->buildStatisticTable($subgroup, $font, $type, $variety, $origin, $destiny, $years);        
        $jsonTable = utf8_encode($jsonTable);
        $jsonTable = json_decode($jsonTable);
        return $this->jsonResponse->response(true, null)->addValue("tabela", $jsonTable)
                                  ->withoutHeader()->serialize();
    }
    
    public function getDistinctStatisticTable($g1, $g2, array $years){
        $jsonTable = $this->buildStatisticTableSearchingDistinctGroups($g1, $g2, $years);
        return $this->buildTableJsonResponse($jsonTable);
    }
    
    //POST ://datacenter/save
    public function saveValues(ExcelInputFile $excelInputFile, $subgroup, $font, $destiny, $coffeType, $variety){
        if(SessionAdmin::isLogged()){
            try{
                if($this->datacenterService->insertValues($excelInputFile, $subgroup, $destiny, $coffeType, $variety, $font)){
                    return $this->jsonResponse->response(true, "Dados inseridos com sucesso!")->serialize();
                }else{
                    $message = "Dados não inseridos. Verifique a possibilidade de já existirem dados referentes a esta planilha";
                    return $this->jsonResponse->response(true, $message)->serialize();
                }
            }catch(Exception $e){
                return $this->jsonResponse->response(false, $e->getMessage())->serialize();
            }
        }else{
            throw new LoginException();
        }
    }
        
    /**actions**/
    
    public function buildChart($subgroup, $font, $type, $variety, $origin, $destiny, $years) {
        return $this->buildAnything("chart", $subgroup, $font, $type, $variety, $origin, $destiny, $years);
    }
    
    public function buildStatisticTable($subgroup, $font, $type, $variety, $origin, $destiny,array $years = null){
        return $this->buildAnything("statistic", $subgroup, $font, $type, $variety, $origin, $destiny, $years);
    }
    
    public function buildTableAsJson($subgroup, $font, $type, $variety, $origin, $destiny,array $years = null) {
        return $this->buildAnything("table", $subgroup, $font, $type, $variety, $origin, $destiny, $years);
    }
    
    public function buildExcelTable($subgroup, $font, $type, $variety, $origin, $destiny, array $years){
        return $this->buildAnything("spreadsheet", $subgroup, $font, $type, $variety, $origin, $destiny, $years);
    }

    public function buildTableSearchingDistinctGroups($g1, $g2, $years) {
        $groups = array($g1,$g2);
        return $this->generalBuilderForTwoDifferentGroupsSelected("table", $groups, $years);
    }
    
    public function buildStatisticTableSearchingDistinctGroups($paramsGroup1, $paramsGroup2, $years) {
        $groups = array($paramsGroup1,$paramsGroup2);
        return $this->generalBuilderForTwoDifferentGroupsSelected("statistic", $groups, $years);        
    }

    public function buildChartSearchingDistinctGroups($g1, $g2, $years) {        
        $groups = array($g1,$g2);
        return $this->generalBuilderForTwoDifferentGroupsSelected("chart",$groups,$years);
    }

    private function generalBuilderForTwoDifferentGroupsSelected($builderType, array $groups, $years){
        $array_groups = array();
        foreach($groups as $g){
            $group_values = $this->getValues($g["subgroup"],$g['font'],$g['type'],$g['variety'],$g['origin'],$g['destiny'],$years);
            $group = $this->grouper->groupDataValues($this->getListAsAnArrayObject($group_values));
            array_push($array_groups, $group);
        }        
        return $this->buildForGroupedData($builderType, $array_groups, $years);        
    }
    
    private function buildAnything($builderType, $subgroup, $font, $type, $variety, $origin, $destiny,array $years = null){        
        $asJson = $this->asJson;
        $this->asJson = false;
        $values = $this->getValues($subgroup, $font, $type, $variety, $origin, $destiny,$years);        
        $this->asJson = $asJson;
        if($values instanceof HashMap){
            $listValues = $values->values();
            $group1 = $this->grouper->groupDataValues($this->getListAsAnArrayObject($listValues->offsetGet(0)));
            $group2 = $this->grouper->groupDataValues($this->getListAsAnArrayObject($listValues->offsetGet(1)));
            return $this->buildForGroupedData($builderType, array($group1, $group2), $years);
        }else{
            $groupedValues = $this->grouper->groupDataValues($this->getListAsAnArrayObject($values));
            return $this->buildForGroupedData($builderType, $groupedValues, $years);   
        }
    }
    
    private function buildForGroupedData($builderType, $groupedValues, array $years){
        if(is_array($groupedValues)){
            if($builderType == 'chart'){
                if($groupedValues[0]->values()->count() > 0 && $groupedValues[1]->values()->count() > 0)
                    $this->chartType = "MSColumn3DLineDY.swf";
                else
                    $this->chartType = "MSLine.swf";
            }
        }else{
            $this->chartType = "MSLine.swf";
        }
        return $this->getBuilder($builderType)->build($groupedValues, $years);
    }
    
    private function getListAsAnArrayObject($list){
        if($list instanceof ArrayIterator){
            $list = new ArrayObject($list->getArrayCopy());
        }
        return $list;
    }
 
    public function getValues($subgroup, $font, $type, $variety, $origin, $destiny,array $years = null) {
        if(!$this->anyValueIsAnArray($subgroup, $font, $type, $variety, $origin, $destiny)){
            return $this->getValuesWithSimpleParams($subgroup, $font, $type, $variety, $origin, $destiny,$years);
        }else{            
            if(is_array($subgroup)){                
                return $this->getValuesFilteringByTwoSubgroups($subgroup, $font, $type, $variety, $origin, $destiny);
            }else{                
                return $this->getValuesWithMultipleParams($subgroup, $font, $type, $variety, $origin, $destiny);
            }
        }
    }
    
    private function anyValueIsAnArray($subgroup, $font, $type, $variety, $origin, $destiny){
        return (is_array($subgroup) || is_array($font) || is_array($type) || is_array($variety) 
                || is_array($origin) || is_array($destiny));
    }
    
    public function getValuesWithSimpleParams($subgroup, $font, $type, $variety, $origin, $destiny,array $years = null) {
        $values = $this->datacenterService->getValuesWithSimpleFilter($subgroup, $variety, $type, $origin, $destiny, $font,$years);
        if($this->asJson)
            return $this->toJson($values);        
        return $values;
    }
        
    public function getValuesWithMultipleParams($subgroup, $font, $type, $variety, $origin, $destiny,array $years = null) {
        $values = $this->datacenterService->getValuesFilteringWithMultipleParams($subgroup, $variety, $type, $origin, $destiny, $font,$years);
        if($this->asJson)
            return $this->toJson($values);        
        return $values;
    }
        
    public function getValuesFilteringByTwoSubgroups(array $subgroup, $font, $type, $variety, $origin, $destiny, array $years = null) {
        $values = $this->datacenterService->getValuesFilteringWithMultipleParams($subgroup, $variety, $type, $origin, $destiny, $font,$years);
        if($this->asJson)
            return $this->hashMapFilteredToJSON($values);
        return $values;
    }
    
    public function calculateSampleStandardDeviation($group){
        $values = $this->datacenterRepository->getValuesFromAGroup($group);
        $standarDeviation = $this->statistic->sampleStandardDeviation($values);
        
        return $this->jsonResponse->response(true, null)
                ->addValue("value", $standarDeviation)
                ->withoutHeader()
                ->serialize();
    }
    
    private function hashMapFilteredToJSON(Map $map){        
        $json = '{';
        $listValues = $map->values()->getIterator();
        $n = 1;
        while($listValues->valid()){
            $json .= '"subgroup_'.$n++.'":';
            $json .= $this->toJson($listValues->current()->getIterator());
            if(($n-1) < $listValues->count())
                $json .= ',';
            $listValues->next();
        }
        $json .= '}';
        return $json;
    }
    
    private function toJson(ArrayIterator $list){
        $json = "[";
        while($list->valid()){
            $json .= $list->current()->toJson();
            $json .= ",";
            $list->next();
        }
        $json = substr($json, 0, -1);
        $json .= "]";
        return $json;
    }
}
?>

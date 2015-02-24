<?
//$_SERVER["DOCUMENT_ROOT"] = '/путь/к/файлу'; //раскомментировать
set_time_limit (0);

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
if(!CModule::IncludeModule("iblock")) return false;
if(!CModule::IncludeModule("catalog")) return false;
if(!CModule::IncludeModule("sale")) return false;

$arID = array();

$arBasketItems = array();
$date = new DateTime();
$date->modify('-30 day');
$DD = $date->format('d-m-Y');
echo $DD;
$dbBasketItems = CSaleBasket::GetList(
     array(
			"NAME" => "ASC",
			"ID" => "ASC"
		),
     array(
			"LID" => 's1',
			"ORDER_ID" => "NULL",
			'>=DATE_UPDATE'=>$DD
		),
     false,
     false,
     array()
);

$result = array();
$index = 0;
while ($arItems = $dbBasketItems->Fetch()) {
	// проверка наличия товара в заказах за последний месяц
	$flag=0;
	$dbBasketItemsOld = CSaleBasket::GetList(
		 array("NAME" => "ASC","ID" => "ASC"),
		 array("LID" => 's1','>=DATE_UPDATE'=>$DD,"PRODUCT_ID"=>$arItems['PRODUCT_ID'],">=ORDER_ID"=>"1"),
		 false,
		 false,
		 array()
	);
	if ($arItemsOld = $dbBasketItemsOld->Fetch()) {
		$flag = 1;
	}
	// данные пользователя
	if (empty($result[$arItems['FUSER_ID']]['FIO'])) {
		$rsUser = CUser::GetByID($arItems['FUSER_ID']);
		$arUser = $rsUser->Fetch();
		$result[$arItems['FUSER_ID']]['FIO'] = $arUser['NAME'] .' '.$arUser['LAST_NAME'];
		$result[$arItems['FUSER_ID']]['EMAIL'] = $arUser['EMAIL'];
	}
	
	if ($arItems['FUSER_ID'] <> $userId)
		$index = 0;
	
	// сохранения названия товаров
	if ($flag == 0) {
		if (!empty($result[$arItems['FUSER_ID']]['DATA']['NAME']))
			$result[$arItems['FUSER_ID']]['DATA']['NAME'] = $result[$arItems['FUSER_ID']]['DATA']['NAME'].', '.$arItems['NAME'];
		else 
			$result[$arItems['FUSER_ID']]['DATA']['NAME'] = $arItems['NAME'];
		$userId = $arItems['FUSER_ID'];
		$index++;
	}

}
foreach ($result as $k => $v) {

    $aSendFields = array(
		'NAME'=>$v['FIO'],
		'TEXT'=>$v['NAME'],
		'EMAIL'=>$v['EMAIL']
	);
	$resSend = CEvent::Send('НАЗВАНИЕ_ШАБЛОНА', array('s1'), $aSendFields);//заменить шаблон
}
?>
/*  Основной метод отправки.
	var config = {
		url: '/test/',
		dataType: 'html',
		type: 'POST',
		data: getData("testDataForm"),
		beforeSend: function(){},
		success: function(data, textStatus){reloadHTML(data, "data");}
	};
	sendRequest(config);
*/
function sendRequest(objConfig)
{
	var url = objConfig.url || '/';
	var data = objConfig.data || {};
	var dataType = objConfig.dataType || 'html';
	var type = objConfig.type || 'GET';
	var async = objConfig.async || true;
	var successFunction = objConfig.success || function(){};
	var errorFunction = objConfig.error || function(){};
	var beforeSendFunction = objConfig.beforeSend || function(){};
	var objConfigFunction = objConfig.objConfig || function(){};

 	$.ajax({
	    url: url,
	    data: data,
	    dataType : dataType,
	    type: type,
	    async: async,
	    success: function (data, textStatus)
	    {
	    	successFunction(data, textStatus);
	    },
	    error: function()
	    {
	    	errorFunction();
	    },
	    beforeSend: function()
	    {
	    	beforeSendFunction();
	    },
	    complete: function()
	    {
	    	objConfigFunction();
	    }
	});
	
	return false;
}

/* Метод замены/перезагрузки куска HTML
	Довольно тонкая вещь, требует нормальной верстки
	data - DOM полученный например подзапросом к серверу
	tagID - id элемента.
*/
function reloadHTML(data, tagID)
{
	$('#'+tagID).replaceWith($(data).find("#"+tagID));
}

/*	Функция собирает и подготавливает к отправке данные из формы

	takeFormData("dataFormID");
*/
function getData(formID)
{
	return $('#'+formID).serialize();
}


export class MethodsJSON {

constructor() {}

read() {
    $.ajax({
        url:"http://ep.abnet.sk",
        method:'POST',
        data:{
            protection:'ABNet',
            user:'userRemotePoint',
            pass:'enigma',
            serial:'123-ABC',
            prefix:'apiTest',
            SQL:'SELECT ' + 
                    'pers_id, ' +
                    'pers_name, ' + 
                    'pers_surname, ' +
                    'pers_birth, ' + 
                    'street_zip, '+
                    'street_name, '+ 
                    'pers_number, '+
                    'pers_color, '+
                    'city_name '+
                    'FROM '+
                    'person, '+
                    'street, '+
                    'city WHERE pers_idStreet = street_id AND street_city = city_id ',
            
            childrenData: 'pers_id=>relJobs_persId(relJobs_jobId=>jobs_id):|:' +
                            'pers_id=>open_persId(open_day=>days_id):|:' +
                            'pers_id=>relKnow_persId(relKnow_knowId=>know_id:||:' +
                            'relKnow_degreeId=>degree_id )',
        },
        success:function(data)  {
            (async () => {  
                
                $('#JSON').html(data);
                $('body').prepend('<div class="viewDemo"></div>');
                $(".viewDemo").append('<span class="viewLink">Zobraziť v HTML</span>');
                $(".viewLink").unbind();
                $(".viewLink").click(function() {
                    let methodsJSON = new MethodsJSON();
                    methodsJSON.viewInHTML();
                });
            })();
        }                                                       
    });
}

viewInHTML() {
    $('.viewDemo').html('');
    let content = JSON.parse($('#JSON').html());
    $('.viewDemo').append('<div class="global container"></div>');
    content.map((person, index) => {
        $('.global').append('<div class="element" id="' + person.pers_id + '"></div>');
        $('#'+person.pers_id).css({'background-color': person.pers_color});
        $('#'+person.pers_id).append('<div id="rowName_'+person.pers_id+'" class="row"></div>');
        $('#rowName_'+person.pers_id).append('<div class="col-sm-3 inLine mt-1">Meno a priezvisko:</div>');
        $('#rowName_'+person.pers_id).append('<div id="rowNameIn_' + person.pers_id+ '"  class="col-sm-9 inAfter mt-1"></div>');
        $('#rowNameIn_'+person.pers_id).html('<b>'+person.pers_name+' '+person.pers_surname+' &nbsp;&nbsp;&nbsp;&nbsp; (Personálne číslo: '+ person.pers_id +')</b>')
    
        $('#'+person.pers_id).append('<div id="rowResidence_'+person.pers_id+'" class="row"></div>');
        $('#rowResidence_'+person.pers_id).append('<div class="col-sm-3 inLine mt-1">Trvalé bydlisko:</div>');
        $('#rowResidence_'+person.pers_id).append('<div id="rowResidenceIn_' + person.pers_id+ '"  class="col-sm-9 inAfter mt-1"></div>');
        $('#rowResidenceIn_'+person.pers_id).html('<b>'+person.street_name+' '+person.pers_number+'</b>');

        $('#'+person.pers_id).append('<div id="rowSeat_'+person.pers_id+'" class="row"></div>');
        $('#rowSeat_'+person.pers_id).append('<div class="col-sm-3 inLine mt-1">Trvalé bydlisko:</div>');
        $('#rowSeat_'+person.pers_id).append('<div id="rowSeatIn_' + person.pers_id+ '"  class="col-sm-9 inAfter mt-1"></div>');
        $('#rowSeatIn_'+person.pers_id).html('<b>'+person.street_zip+' '+person.city_name+'</b>');

        $('#'+person.pers_id).append('<div id="rowProfession_'+person.pers_id+'" class="row"></div>');
        $('#rowProfession_'+person.pers_id).append('<div class="col-sm-3 inLine mt-1">Profesia:</div>');
        $('#rowProfession_'+person.pers_id).append('<div id="rowProfessionIn_' + person.pers_id+ '"  class="col-sm-9 inAfter mt-1"></div>');
        $('#rowProfessionIn_'+person.pers_id).append('<ul id="ulProfession_'+person.pers_id+'" class="small"></ul>');
        person.relJobs.map((jobLine, index) => {
            $('#ulProfession_'+person.pers_id).append('<li>'+jobLine.jobs_name+'</li>');
        });
        
        $('#'+person.pers_id).append('<div id="rowKnow_'+person.pers_id+'" class="row"></div>');
        $('#rowKnow_'+person.pers_id).append('<div class="col-sm-3 inLine mt-1">Znalosti:</div>');
        $('#rowKnow_'+person.pers_id).append('<div id="rowKnowIn_' + person.pers_id+ '"  class="col-sm-9 inAfter mt-1"></div>');
        $('#rowKnowIn_'+person.pers_id).append('<ul id="ulKnow_'+person.pers_id+'" class="small"></ul>');
        person.relKnow.sort((a, b) => (a.know_name > b.know_name) ? 1 : -1)
            .map((lineKnow) => {
                $('#ulKnow_'+person.pers_id).append('<li>'+lineKnow.know_name+' &nbsp;&nbsp;&nbsp;('+lineKnow.degree_name+')</li>');
            });

        $('#'+person.pers_id).append('<div id="rowAvail_'+person.pers_id+'" class="row"></div>');
        $('#rowAvail_'+person.pers_id).append('<div class="col-sm-3 inLine mt-1">K dispozícii je:</div>');
      
        $('#rowAvail_'+person.pers_id).append('<div id="rowAvailIn_' + person.pers_id+ '"  class="col-sm-9 inAfter mt-1"></div>');        
      
        if(person.open.length>0) {
            $('#rowAvailIn_'+person.pers_id).append('<table id="table_'+person.pers_id+'"></table>');
            $('#table_'+person.pers_id).css({'width':'100%'});
            $('#table_'+person.pers_id).append('<thead id="thead_'+person.pers_id+'"></thead>');
            $('#thead_'+person.pers_id).append('<tr id="trThead_'+person.pers_id+'"></tr>');
            $('#trThead_'+person.pers_id).append('<td style="width:25%"><b>Dňa:</b></td>'); 
            $('#trThead_'+person.pers_id).append('<td style="width:25%"><b>OD:</b></td>'); 
            $('#trThead_'+person.pers_id).append('<td style="width:25%"><b>DO:</b></td>'); 
            $('#trThead_'+person.pers_id).append('<td style="width:25%"></td>'); 

            person.open.sort((a, b) => (a.days_id > b.days_id) ? 1 : -1)
                .map((openLine,rowNumber) => {
                    $('#table_'+person.pers_id).append('<tbody id="tbody_'+person.pers_id+'_'+rowNumber+'"></tbody>');        
                    $('#tbody_'+person.pers_id+'_'+rowNumber).append('<tr id="trTbody_'+person.pers_id+'_'+rowNumber+'"></tr>');    
                    $('#trTbody_'+person.pers_id+'_'+rowNumber).append('<td style="width:25%">'+openLine.days_name+':</td>'); 
                    $('#trTbody_'+person.pers_id+'_'+rowNumber).append('<td style="width:25%">'+openLine.open_from+'</td>'); 
                    $('#trTbody_'+person.pers_id+'_'+rowNumber).append('<td style="width:25%">'+openLine.open_to+'</td>'); 
                    $('#trTbody_'+person.pers_id+'_'+rowNumber).append('<td style="width:25%"></td>'); 
            });
        
        } else {
            $('#rowAvailIn_'+person.pers_id).html('<span class="warning"><b>Momentálne nie je k dispozícii</b></span>');
        }
    
              
    });
    
}    
    
}


var Chance = require('chance');
var chance = new Chance();

var express = require('express');
var app = express();

app.get('/',function(req,res){
    res.send(generateAdress());
});

app.listen(3000,function(){
    console.log("Accepting HTTP resquest on port 3000");
});

function generateAdress(){
    var numberOfAdresses = chance.integer({
        min : 1,
        max : 10,
    });

    var adresses = [];

    for(var i = 0; i < numberOfAdresses; ++i){
        adresses.push({
            'street' : chance.street(),
            'city' : chance.city(),
            'postal' : chance.postal(),
            'country' : chance.country({full : true}),
        });
    }
    console.log(adresses);
    return adresses;
}
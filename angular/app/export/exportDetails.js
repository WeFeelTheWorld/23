(function(){
    "use strict";

    angular.module('app.controllers').controller('ExportDetailsCtrl', function($state){
        //
        var vm = this;
        vm.export = {
          list:[]
        };
        vm.selected = [];
        vm.options = {
          indizes:{
            addClick: function(){
              $state.go('app.index.exports.details.add');
            },
    				addContainerClick:function(){
    					var item = {
    						title: 'I am a group... name me'
    					};
    					vm.export.list.push(item);
    				},
    				deleteClick:function(){
    					angular.forEach(vm.selected,function(item, key){
    							removeItem(item,vm.export.list);
    							vm.selected = [];
    					});
    				},
    				deleteDrop: function(event,item,external,type){
    						removeItem(item,vm.export.list);
    						vm.selection = [];
    				}
          },
          style:{
            click: function(item){
              console.log(item);
              $state.go('app.index.exports.details.style',{styleId:item.id, styleName:item.name})
            }
          },
          withSave: true,
          styleable: true
        };
        function removeItem(item, list){
    			angular.forEach(list, function(entry, key){
    				if(entry.id == item.id){
    					list.splice(key, 1);
    					return true;
    				}
    				if(entry.children){
    					var subresult = removeItem(item, entry.children);
    					if(subresult){
    						return subresult;
    					}
    				}
    			});
    			return false;
    		}
    });

})();

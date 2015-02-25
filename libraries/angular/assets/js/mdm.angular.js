angular.module('mdm.angular',[])
    .directive('onLastRepeat',function(){
        return {
            scope: {
                cb: '&onLastRepeat',
            },
            link: function (scope,element) {
                if (scope.$parent.$last) {
                    setTimeout(function () {
                        scope.cb(element);
                    }, 0);
                }
            }
        };
    })
    
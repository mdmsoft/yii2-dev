var $location = $injector.get('$location');
var search = $location.search();
var $filter = $injector.get('$filter');
var $modal = $injector.get('$modal');


$scope.rows = [];
query = function () {
    Item.query({
        type: 1,
    }, function (rows) {
        $scope.rows = rows;
        $scope.filter();
    });
}
query();

// data provider
$scope.provider = {
    offset: 0,
    page: 1,
    itemPerPage: 20,
    paging: function () {
        $scope.provider.offset = ($scope.provider.page - 1) * $scope.provider.itemPerPage;
    }
};

$scope.filter = function () {
    $scope.filtered = $filter('filter')($scope.rows, {type: $scope.qT, '$': $scope.q});
}

$scope.openModal = function () {
    $modal.open(angular.extend(dAdmin.views['/role/create'], {
        animation: true,
        resolve: {
            type: function () {
                return 1;
            }
        }
    })).result.then(function () {
        addAlert('info', 'New role added');
        query();
    });
}

$scope.deleteItem = function (item) {
    if (confirm('Are you sure you want to delete?')) {
        Item.remove({id: item.name}, {}, function () {
            addAlert('info', 'Role deleted');
        }, function (r) {
            addAlert('error', r.statusText);
        });
    }
}

$scope.alerts = [];
addAlert = function (type, msg) {
    var alert = {type: type, msg: msg};
    if (type == 'info') {
        alert.timeout = 3000;
    }
    $scope.alerts.push(alert);
};

$scope.closeAlert = function (index) {
    $scope.alerts.splice(index, 1);
};
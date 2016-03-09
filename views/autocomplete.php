<div ng-controller="AutocompleteController">
    <div class="row">
        <div class="col-md-2">
            <label>Search</label>
        </div>
        <div class="col-md-10">
            <div class="form-group">
                <input type="text" autocomplete="off" name="autocomplete" class="form-control" ng-model="autocomplete" uib-typeahead="item.autocomplete for item in autocompleteItem($viewValue)" typeahead-loading="loading" typeahead-no-results="noResults" typeahead-template-url="autocomplete-item" typeahead-popup-template-url="autocomplete-wrapper" typeahead-on-select="onSelectAutocomplete($item)" />
                <input type="hidden" name="<?= $autocomplete->getInputName()->attr(); ?>" ng-value="item.id" />
            </div>
        </div>
    </div>
</div>

<script type="text/ng-template" id="autocomplete-item">
    <td ng-repeat="col in match.model">{{ col }}</td>
</script>
<script type="text/ng-template" id="autocomplete-wrapper">
    <div class="dropdown-menu p-autocomplete" ng-show="isOpen() && !moveInProgress" ng-style="{top: position().top+'px', left: position().left+'px'}" role="listbox" aria-hidden="{{!isOpen()}}">
        <table class="table table-stripped">
            <tr ng-repeat="match in matches track by $index" ng-class="{active: isActive($index) }" ng-mouseenter="selectActive($index)" ng-click="selectMatch($index, $event)" role="option" id="{{::match.id}}">
                <td uib-typeahead-match index="$index" match="match" query="query" template-url="templateUrl"></td>
            </tr>
        </table>
    </div>
</script>
<script>
    nzshores.controller('AutocompleteController', ['$scope', '$http', function ($scope, $http) {
        $scope.autocompleteItem = function(value) {
            var keys = <?= $autocomplete->getKeys()->json(); ?>;
            return $http.get(<?= $autocomplete->getUrl()->json(); ?>, {
                params: {
                    'search[value]': value,
                },
            }).then(function(response) {
                return response.data.data.map(function(item) {
                    var row = {};
                    for (var key in item) {
                        if (keys.indexOf(key) !== -1) {
                            row[key] = item[key];
                        }
                    }
                    return row;
                });
            });
        };
        $scope.onSelectAutocomplete = function(item) {
            var result = [];
            for (var key in item) {
                if (item[key]) {
                    result.push(item[key]);
                }
            }
            $scope.autocomplete = result.join(', ');
            $scope.item = item;
        }
    }]);
</script>

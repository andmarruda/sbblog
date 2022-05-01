@extends('templates.adminTemplate')

@section('page')
    @php
        function intervalToText($secs){
            $secs = (int) $secs;
            $text='';
            $min = (int) ($secs / 60);
            if($min > 0){
                $text .= $min. ' minuto(s) ';
                $secs = $secs - ($min * 60);
            }
            $text .= $secs. ' segundo(s)';
            return $text;
        }
    @endphp

    <div class="row" style="margin-top:20px;">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    10 Categorias mais acessadas
                </div>
                <div class="card-body">
                    <canvas id="categoryChart" width="250" height="250" style="margin: 20px auto;"></canvas>
                    <table class="table table-bordered table-striped table-hover" id="gridCategoryStatistis">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Categoria</th>
                                <th>Número de visitas</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    Artigos com maior tempo de permanência
                </div>
                <div class="card-body">
                    <canvas id="articlesChart" width="250" height="250" style="margin: 20px auto;"></canvas>
                    <table class="table table-bordered table-striped table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Artigo</th>
                                <th>Tempo médio</th>
                                <th>Menor tempo</th>
                                <th>Maior tempo</th>
                                <th>Nº visitas</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($artStat as $stat)
                            <tr>
                                <td><span class="badge" style="background-color:{{$stat->article_color}};">&nbsp;</span></td>
                                <td>{{$stat->title}}</td>
                                <td>{{intervalToText($stat->avg_sec)}}</td>
                                <td>{{intervalToText($stat->min_sec)}}</td>
                                <td>{{intervalToText($stat->max_sec)}}</td>
                                <td>{{$stat->total_visits}}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12" style="margin-top:20px;">
            <h5>Últimos artigos</h5>
        </div>

        @include('utils.articleCard', ['articles' => $articles])
    </div>

    <script src="{{asset('js/chart.min.js')}}"></script>
    <script>
        const catStat = {!!$catStat!!};
        const artStat = {!!$artStat->toJson()!!};
        
        document.addEventListener('DOMContentLoaded', () => {
            var categoryChart = document.getElementById("categoryChart");
            var catChart = new Chart(categoryChart, {
                type: 'pie',
                data: {
                    labels: getColumn(catStat, 'category'),
                    datasets: [{
                    label: 'Categorias mais acessadas',
                    data: getColumn(catStat, 'total_visits'),
                    backgroundColor: getColumn(catStat, 'color'),
                    borderColor: getColumn(catStat, 'color'),
                    borderWidth: 1
                    }]
                },
                options: {
                    responsive: false,
                }
            });

            let htmlCatGrid = '';
            for(let cs of catStat){
                htmlCatGrid += '<tr>\
                    <td><span class="badge" style="background-color:'+ cs.color +';">&nbsp;</span></td>\
                    <td>'+ cs.category +'</td>\
                    <td>'+ cs.total_visits +'</td>\
                </tr>';
            }
            document.querySelector('#gridCategoryStatistis tbody').innerHTML = htmlCatGrid;

            var articleChart = document.getElementById("articlesChart");
            var artChart = new Chart(articleChart, {
                type: 'pie',
                data: {
                    labels: getColumn(artStat, 'title'),
                    datasets: [{
                    label: 'Artigos com maior tempo de permanência',
                    data: getColumn(artStat, 'avg_sec'),
                    backgroundColor: getColumn(artStat, 'article_color'),
                    borderColor: getColumn(artStat, 'article_color'),
                    borderWidth: 1
                    }]
                },
                options: {
                    responsive: false,

                }
            });
        });
    </script>
@endsection
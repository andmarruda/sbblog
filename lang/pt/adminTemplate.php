<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Admin Template Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used by admin template in general.
    | You are free to change them to anything
    | you want to customize your views to better match your application.
    |
    */

    'menu.dashboard'                => 'Dashboard',
    'menu.article'                  => 'Artigo',
    'menu.article.new'              => 'Novo',
    'menu.article.list'             => 'Listar',
    'menu.article.category'         => 'Categoria',
    'menu.config'                   => 'Configurações',
    'menu.config.user'              => 'Usuário',
    'menu.config.password'          => 'Alterar senha',
    'menu.config.general'           => 'Geral',
    'menu.logout'                   => 'Sair',
    'footer.copyright'              => 'Todos os direitos reservados.',

    'modal.logout.question'         => 'Deseja sair do sistema?',
    'modal.logout.btn.logout'       => 'Sair',
    'modal.logout.btn.cancel'       => 'Continuar logado',

    'modal.password.title'          => 'Alterar senha',
    'modal.password.oldpass'        => 'Senha atual',
    'modal.password.newpass'        => 'Nova senha',
    'modal.password.confpass'       => 'Confirmar Nova senha',
    'modal.password.btn.submit'     => 'Alterar senha',

    'password.safetyMessage'        => 'Aumente a segurança da sua senha! Sua senha deve conter pelo menos 1 letra, 1 número e pelo menos 8 caracteres.',
    'password.diffPassword'         => 'Nova senha e confirmação da nova senha não é correspondente!',
    'password.verifyCurrentErr'     => 'Senha antiga não corresponde com o usuário logado!',
    'password.changed'              => 'Senha alterada com sucesso!',

    'form.btn.save'			        => 'Salvar',
    'form.btn.search'		        => 'Pesquisar',
    'form.btn.change'               => 'Editar',
    'form.btn.cleanFilter'          => 'Limpar filtro',
    'form.btn.addArticle'           => 'Adicionar Artigo',
    'form.btn.addTag'               => 'Adicionar Tag',
    'form.btn.removeTag'            => 'Remover Tag',
    'form.load.advice'		        => 'Clique 2x no(a) :desired desejado(a) para carregá-lo(a) no formulário.',
    'form.active'		            => 'Ativo?',
    'form.active.select'            => 'Selecione...',
    'form.active.true'              => 'Ativo',
    'form.active.false'             => 'Inativo',

    'dashboard.stat.category.title' => 'Top 10 - Categorias acessadas',
    'dashboard.stat.category.id'    => '#',
    'dashboard.stat.category.label' => 'Categoria',
    'dashboard.stat.category.visits'=> 'Nº de visitas',
    'dashboard.stat.article.title'  => 'Top 10 - Artigos com maior permanência',
    'dashboard.stat.article.id'     => '#',
    'dashboard.stat.article.label'  => 'Artigo',
    'dashboard.stat.article.avgtime'=> 'Tempo médio',
    'dashboard.stat.article.mintime'=> 'Menor tempo',
    'dashboard.stat.article.maxtime'=> 'Maior tempo',
    'dashboard.stat.article.visits' => 'Nº de visitas',
    'dashboard.article.latest'      => 'Últimos artigos',

    'category.title'		        => 'Categoria',
    'category.form.label'		    => 'Categoria',
    'category.okmessage'		    => 'Categoria salva com sucesso!',
    'category.errmessage'		    => 'Erro ao salvar a categoria!',
    'category.modal.title'		    => 'Pesquisar categoria',
    'category.modal.input'		    => 'Categoria',
    'category.modal.tab.label'      => 'Categoria',

    'user.title'                    => 'Usuário',
    'user.firstUser.advice'         => 'Para maior segurança do sistema, crie um novo usuário! Após isso o sistema irá desativar o usuário de configuração!',
    'user.form.name'                => 'Nome',
    'user.form.user'                => 'Usuário "Email"',
    'user.form.pass'                => 'Senha',
    'user.form.confirmPass'         => 'Confirmação de senha',
    'user.okmessage'                => 'Usuário salvo com sucesso!',
    'user.errmessage'               => 'Erro ao salvar o usuário!',
    'user.modal.title'              => 'Pesquisar usuário',
    'user.modal.input'              => 'Usuário',
    'user.modal.grid.user'          => 'Usuário',
    'user.modal.grid.name'          => 'Nome',

    'general.title'                 => 'Configuração Geral',
    'general.form.slogan'           => 'Slogan',
    'general.form.niche'            => 'Nicho do Blog',
    'general.form.niche.small'      => '"Ex.:Tecnologia, esporte, etc..."',
    'general.brandImage'            => 'Logotipo',
    'general.okmessage'             => 'Configuração geral salva com sucesso!',
    'general.errmessage'            => 'Erro ao salvar configuração geral!',
    'general.imageError'            => 'Arquivo fora dos padrões permitidos. Verifique a extensão da imagem e o tamanho da imagem.',

    'articleCard.createdBy'         => 'Criado por',
    'articleCard.createdTime'       => 'em',
    'articleCard.category'          => 'Categoria:',
    'articleCard.lastUpdate'        => 'Último update:',
    'articleCard.views'             => 'visualizações',
    'articleCard.avgStayTime'       => 'Tempo médio permanência:',
    'articleCard.noArticle'         => 'Nenhum artigo cadastrado!',

    'articleList.title'             => 'Listagem de artigo',
    'articleList.latest'            => 'Últimos artigos',
    'articleList.searchInput'       => 'Pesquisar por',
    'articleList.searchInput.small' => 'Título ou conteúdo',

    'article.title'                 => 'Artigo',
    'article.form.title'            => 'Título',
    'article.form.previewFolder'    => 'Pré visualização da capa',
    'article.form.cover'            => 'Capa',
    'article.form.category'         => 'Categoria',
    'article.form.category.select'  => 'Selecione...',
    'article.form.meta'             => 'Descrição curta',
    'article.form.meta.small'       => 'até 200 caracteres',
    'article.form.article'          => 'Artigo',
    'article.okmessage'             => 'Artigo salvo com sucesso!',
    'article.errmessage'            => 'Erro ao salvar o usuário!',
    'article.require.article'       => 'Preencha um artigo para poder salvá-lo!',
    'article.require.cover'         => 'Escolha a capa para o seu artigo!',
    'article.tag.duplicate1'        => 'A tag',
    'article.tab.duplicate2'        => 'já foi inserida',
    'article.form.imageNotNull'     => 'Envie uma capa para o seu artigo!',
    'article.form.imageNotValid'    => 'Ocorreu um problema ao enviar o arquivo, por favor tente novamente!',
    'article.form.extensionErr'     => 'A extensão :extension não é permitida!',
    'article.form.sizeErr'          => 'O arquivo de capa excede o limite de :mbyte Mb',
];

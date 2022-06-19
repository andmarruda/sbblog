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
    'menu.article'                  => 'Article',
    'menu.article.new'              => 'New',
    'menu.article.list'             => 'List',
    'menu.article.category'         => 'Category',
    'menu.config'                   => 'Settings',
    'menu.config.user'              => 'User',
    'menu.config.password'          => 'Change Password',
    'menu.config.general'           => 'General',
    'menu.logout'                   => 'Logout',
    'footer.copyright'              => 'All rights reserved.',

    'modal.logout.question'         => 'Did you want to proceed with logout?',
    'modal.logout.btn.logout'       => 'Logout',
    'modal.logout.btn.cancel'       => 'Continue logged in',

    'modal.password.title'          => 'Change password',
    'modal.password.oldpass'        => 'Password',
    'modal.password.newpass'        => 'New password',
    'modal.password.confpass'       => 'Confirm New password',
    'modal.password.btn.submit'     => 'Change Password',

    'password.safetyMessage'        => 'Increase your password security! Your password must contain at least 1 character, 1 number and at least 8 characters.',
    'password.diffPassword'         => 'New password and confirm new password are not equal!',
    'password.verifyCurrentErr'     => 'Old password doesn\'t match with the logged user!',
    'password.changed'              => 'Password changed successfully!',

    'form.btn.save'			        => 'Save',
    'form.btn.search'		        => 'Search',
    'form.btn.change'               => 'Edit',
    'form.btn.cleanFilter'          => 'Reset filter',
    'form.btn.addArticle'           => 'Add Article',
    'form.btn.addTag'               => 'Add Tag',
    'form.btn.removeTag'            => 'Remove Tag',
    'form.load.advice'		        => '2x Click on the desired :desired to load it into the form.',
    'form.active'		            => 'Active?',
    'form.active.select'            => 'Select...',
    'form.active.true'              => 'Active',
    'form.active.false'             => 'Inactive',

    'dashboard.stat.category.title' => 'Top 10 - Categories Accessed',
    'dashboard.stat.category.id'    => '#',
    'dashboard.stat.category.label' => 'Category',
    'dashboard.stat.category.visits'=> 'No. visits',
    'dashboard.stat.article.title'  => 'Top 10 - Long lasting Articles',
    'dashboard.stat.article.id'     => '#',
    'dashboard.stat.article.label'  => 'Article',
    'dashboard.stat.article.avgtime'=> 'Avg time',
    'dashboard.stat.article.mintime'=> 'Min time',
    'dashboard.stat.article.maxtime'=> 'Max time',
    'dashboard.stat.article.visits' => 'No. visits',
    'dashboard.article.latest'      => 'Latest Articles',

    'category.title'		        => 'Category',
    'category.form.label'		    => 'Category',
    'category.okmessage'		    => 'Category saved successfully!',
    'category.errmessage'		    => 'Error saving category!',
    'category.modal.title'		    => 'Search category',
    'category.modal.input'		    => 'Category',
    'category.modal.tab.label'      => 'Category',

    'user.title'                    => 'User',
    'user.firstUser.advice'         => 'To increase system\'s security, create a new user! After this the system will disable the configuration user!',
    'user.form.name'                => 'Name',
    'user.form.user'                => 'User "Email"',
    'user.form.pass'                => 'Password',
    'user.form.confirmPass'         => 'Confirm password',
    'user.okmessage'                => 'User saved successfully!',
    'user.errmessage'               => 'Error saving user!',
    'user.modal.title'              => 'Search User',
    'user.modal.input'              => 'User',
    'user.modal.grid.user'          => 'User',
    'user.modal.grid.name'          => 'Name',

    'general.title'                 => 'General Settings',
    'general.form.slogan'           => 'Slogan',
    'general.form.niche'            => 'Blog Niche',
    'general.form.niche.small'      => '"Ex.:Tecnology, sport, etc..."',
    'general.brandImage'            => 'Brand image',
    'general.okmessage'             => 'General setting saved successfully!',
    'general.errmessage'            => 'Error saving general settings!',
    'general.imageError'            => 'File are not allowed. Check image\'s extension and image size.',
    'general.generalTab'            => 'General',
    'general.socialTab'             => 'Social Network',

    'articleCard.createdBy'         => 'Created by',
    'articleCard.createdTime'       => 'at',
    'articleCard.category'          => 'Category:',
    'articleCard.lastUpdate'        => 'Last update:',
    'articleCard.views'             => 'views',
    'articleCard.avgStayTime'       => 'Average stay time:',
    'articleCard.noArticle'         => 'No article found!',

    'articleList.title'             => 'Article\'s list',
    'articleList.latest'            => 'Latest Articles',
    'articleList.searchInput'       => 'Search by',
    'articleList.searchInput.small' => 'Title or content',
    'articleList.commentNumber'     => 'Amout of comments',

    'article.title'                 => 'Article',
    'article.form.title'            => 'Title',
    'article.form.previewFolder'    => 'Cover preview',
    'article.form.cover'            => 'Cover',
    'article.form.category'         => 'Category',
    'article.form.category.select'  => 'Select...',
    'article.form.premiereDate'     => 'Premiere date',
    'article.form.meta'             => 'Short description',
    'article.form.meta.small'       => 'max 200 characters',
    'article.form.article'          => 'Article',
    'article.tab.form'              => 'Form',
    'article.tab.comment'           => 'Comments',
    'article.commentList.none'      => 'No comments found!',
    'article.okmessage'             => 'Article saved successfully!',
    'article.errmessage'            => 'Error saving article!',
    'article.require.article'       => 'Fill an article to save it!',
    'article.require.cover'         => 'Choose a cover to your article!',
    'article.tag.duplicate1'        => 'Tag',
    'article.tab.duplicate2'        => 'already exists',
    'article.form.imageNotNull'     => 'Send a cover to your article!',
    'article.form.imageNotValid'    => 'Error when trying to send the file, please try it again!',
    'article.form.extensionErr'     => 'Extension :extension are not allowed!',
    'article.form.sizeErr'          => 'Cover file exceeds file size limit of :mbyte Mb',
];

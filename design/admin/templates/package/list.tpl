{* DO NOT EDIT THIS FILE! Use an override template instead. *}
{let page_limit=15
     package_list=fetch( package, list,
                         hash( offset, $view_parameters.offset,
                               limit, $page_limit,
                               repository_id, $repository_id ) )
     repository_list=fetch( package, repository_list )
     can_remove=fetch( package, can_remove )}
<form name="packagelist" method="post" action={concat('package/list',
                            $view_parameters.offset|gt(0)
                            |choose('',
                                    concat('/offset/',$view_parameters.offset)))|ezurl}>

<div class="context-block">

{* ## START messages ## *}
{section show=$remove_list}

{* DESIGN: Header START *}<div class="box-header"><div class="box-tc"><div class="box-ml"><div class="box-mr"><div class="box-tl"><div class="box-tr">

<h1 class="context-title">{'Remove section?'|i18n( 'design/admin/package/list' )}</h1>

{* DESIGN: Mainline *}<div class="header-mainline"></div>

{* DESIGN: Header END *}</div></div></div></div></div></div>

{* DESIGN: Content START *}<div class="box-ml"><div class="box-mr"><div class="box-content">

<div class="message-confirmation">

<h2>{'Removal of packages'|i18n('design/admin/package/list')}</h2>
<p>{'Are you sure you wish to remove the following packages?
The packages will be lost forever.
Note: The packages will not be uninstalled.'|i18n('design/admin/package/list')|break}</p>
<ul>
{section var=package loop=$remove_list}
    <li>
        <input type="hidden" name="PackageSelection[]" value="{$package.name|wash}" />
        {$package.name|wash} ({$package.summary|wash})
    </li>
{/section}
</ul>

</div>

{* DESIGN: Content END *}</div></div></div>

<div class="controlbar">

{* DESIGN: Control bar START *}<div class="box-bc"><div class="box-ml"><div class="box-mr"><div class="box-tc"><div class="box-bl"><div class="box-br">

<div class="block">

    <input class="button" type="submit" name="ConfirmRemovePackageButton" value="{'OK'|i18n('design/admin/package/list')}" />
    <input class="button" type="submit" name="CancelRemovePackageButton" value="{'Cancel'|i18n('design/admin/package/list')}" />

</div>

{* DESIGN: Control bar END *}</div></div></div></div></div></div>

</div>

{section-else}
{section show=$module_action|eq( 'CancelRemovePackage' )}
<div class="message-feedback">
    <p>{'Package removal was canceled.'|i18n('design/admin/package/list')}</p>
</div>
{/section}

{* ## START default window ## *}

{* DESIGN: Header START *}<div class="box-header"><div class="box-tc"><div class="box-ml"><div class="box-mr"><div class="box-tl"><div class="box-tr">

<h1 class="context-title">{'Packages'|i18n('design/admin/package/list')}</h1>

{* DESIGN: Mainline *}<div class="header-mainline"></div>

{* DESIGN: Header END *}</div></div></div></div></div></div>

{* DESIGN: Content START *}<div class="box-ml"><div class="box-mr"><div class="box-content">

<div class="context-attributes">

<div class="block">
<label>{'Repository'|i18n( 'design/admin/package/list' )}</label>
<select name="RepositoryID">
    <option value="">{'All'|i18n( 'design/admin/package/list' )}</option>
{section var=repository loop=$repository_list}
    <option value="{$repository.id|wash}"{section show=eq( $repository.id, $repository_id )} selected="selected"{/section}>{$repository.name|wash}</option>
{/section}
</select>
&nbsp;<input class="button" type="submit" name="ChangeRepositoryButton" value="{'Change repository'|i18n( 'design/admin/package/list' )}" />
</div>


{section show=$package_list}
<table class="list" width="100%" cellpadding="0" cellspacing="0" border="0">
<tr>
    <th class="tight"><img src={'toggle-button-16x16.gif'|ezimage} alt="Invert selection." onclick="ezjs_toggleCheckboxes( document.packagelist, 'PackageSelection[]' ); return false;" title="{'Invert selection.'|i18n( 'design/admin/package/list' )}" /></th>
    <th>{'Name'|i18n('design/admin/package/list')}</th>
    <th>{'Version'|i18n('design/admin/package/list')}</th>
    <th>{'Summary'|i18n('design/admin/package/list')}</th>
    <th>{'Status'|i18n('design/admin/package/list')}</th>
</tr>
{section name=Package loop=$package_list sequence=array(bglight,bgdark)}
<tr class="{$:sequence}">
    {section show=$can_remove}
    <td width="1"><input type="checkbox" name="PackageSelection[]" value="{$:item.name|wash}"/></td>
    {section-else}
      <input type="checkbox" disabled="disabled">
    {/section}
    <td><a href={concat('package/view/full/',$:item.name)|ezurl}>{$:item.name|wash}</a></td>
    <td>{$:item.version-number}-{$:item.release-number}{section show=$:item.release-timestamp}({$:item.release-timestamp|l10n( shortdatetime )}){/section}{section show=$:item.type} [{$:item.type|wash}]{/section}</td>
    <td>{$:item.summary|wash}</td>
    <td>
        {section show=$:item.install_type|eq( 'install' )}
            {section show=$:item.is_installed}
                {'Installed'|i18n('design/admin/package/list')}
            {section-else}
                {'Not installed'|i18n('design/admin/package/list')}
            {/section}
        {section-else}
            {'Imported'|i18n('design/admin/package/list')}
        {/section}
    </td>
</tr>
{/section}
</table>
{section-else}
<p>{'There are no packages matching the selected repository.'|i18n( 'design/admin/package/list' )}</p>
{/section}


</div>
{* DESIGN: Content END *}</div></div></div>

<div class="controlbar">
{* DESIGN: Control bar START *}<div class="box-bc"><div class="box-ml"><div class="box-mr"><div class="box-tc"><div class="box-bl"><div class="box-br">


{let can_create=fetch( package, can_create )
     can_import=fetch( package, can_import )}

<div class="block">
  <input class="button" type="submit" name="RemovePackageButton" value="{'Remove selected'|i18n('design/admin/package/list')}" {section show=and( $package_list|gt( 0 ), $can_remove )|not}disabled="disabled"{/section} />

  <input class="button" type="submit" name="InstallPackageButton" value="{'Import new package'|i18n('design/admin/package/list')}" {section show=$can_import|not}disabled="disabled"{/section}/>
  <input class="button" type="submit" name="CreatePackageButton" value="{'Create new package'|i18n('design/admin/package/list')}" {section show=$can_create|not}disabled="disabled"{/section} />
</div>

{/let}

{* DESIGN: Control bar END *}</div></div></div></div></div></div>
</div>

{/section}

</div>

</form>

{/let}

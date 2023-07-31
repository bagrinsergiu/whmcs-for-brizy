{if $canInstallFree || $hasAssignedBrizyLicense}
    <div class="panel card panel-default mb-3" id="Builder">
        <div class="panel-heading card-header">
            <h3 class="panel-title card-title m-0">{$bPluginName}</h3>
        </div>
        <div class="panel-body card-body text-center">
            <div class="row cpanel-feature-row">

                <app-brizy-installer class="col-xl-4 col-md-6 col-xs-12" pro="{if $canInstallPro}1{else}0{/if}"
                    service-id="{$serviceId}">
                </app-brizy-installer>

                {if !$canInstallPro && is_array(brizyAddonOptions) && count($brizyAddonOptions) > 0 && $freeLicenses > 0}
                    <div class="col-xl-4 col-md-6 col-xs-12">
                        <div class="card">
                            <img class="card-img-top p-3" style="max-height:100px" src="{$bLogo}" alt="{$bPluginName} Pro">
                            <div class="card-body">
                                <h5 class="card-title">{$bPluginName} Pro</h5>
                                <p class="card-text">
                                    {$_lang['client']['template']['serviceMenu']['buyProHeader']['par1'] } {$bPluginName} {$LANG['client']['template']['serviceMenu']['buyProHeader']['par2']}
                                </p>
                                <select id="builder-addon-select" name="builderAddon"
                                    class="form-control custom-select w-100 input-sm form-control-sm">
                                    {foreach from=$brizyAddonOptions item=brizAddon}
                                        <option value="{$brizAddon->id}">{$brizAddon->name}</option>
                                    {/foreach}
                                </select>
                                <button id="builder-addon-buy-button" class="btn btn-default btn-sm btn-block mt-1">
                                    <i class="fas fa-shopping-cart"></i>
                                    Purchase &amp; Activate
                                </button>
                            </div>
                        </div>

                        <script>
                            $("#builder-addon-buy-button").click(() => {
                                const selectedBuilderAddonId = $("#builder-addon-select").val();
                                const targetSelect = $("#cPanelExtrasPurchasePanel select").eq(0).val(
                                    selectedBuilderAddonId);
                                $("#cPanelExtrasPurchasePanel form").eq(0).submit();
                            });
                        </script>
                    </div>
                {/if}
            </div>

        </div>
        <link rel="stylesheet" href="modules/addons/brizy/apps/brizy-admin/styles.css?h={$hash}">
        <script src="modules/addons/brizy/apps/brizy-admin/runtime.js?h={$hash}" defer></script>
        <script src="modules/addons/brizy/apps/brizy-admin/polyfills.js?h={$hash}" defer></script>
        <script src="modules/addons/brizy/apps/brizy-admin/main.js?h={$hash}" defer></script>
    </div>
{/if}   
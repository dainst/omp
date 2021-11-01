{strip}
    {assign var="pageTitle" value="plugins.importexport.datacite.displayName"}
    {include file="common/header.tpl"}
{/strip}
<script type="text/javascript">
	// Attach the JS file tab handler.
	$(function () {ldelim}
		$('#importExportTabs').pkpHandler('$.pkp.controllers.TabHandler');
		$('#importExportTabs').tabs('option', 'cache', true);
        {rdelim});
</script>
<div id="importExportTabs" class="pkp_controllers_tab">
    <ul>
		<li><a href="#settings-tab">{translate key="plugins.importexport.datacite.settings"}</a></li>
        <li><a href="#monographs-tab">{translate key="plugins.importexport.datacite.monographs"}</a></li>
        <li><a href="#chapters-tab">{translate key="plugins.importexport.datacite.chapters"}</a></li>
    </ul>
    <div id="settings-tab">
        <script type="text/javascript">
			$(function () {ldelim}
				$('#dataciteSettingsForm').pkpHandler('$.pkp.controllers.form.FormHandler');
                {rdelim});
        </script>
        <form class="pkp_form" id="dataciteSettingsForm" method="post"
              action="{plugin_url path="settings" verb="save"}">
            {if $doiPluginSettingsLinkAction}
                {fbvFormArea id="doiPluginSettingsLink"}
                {fbvFormSection}
                    {include file="linkAction/linkAction.tpl" action=$doiPluginSettingsLinkAction}
                {/fbvFormSection}
                {/fbvFormArea}
            {/if}
            {fbvFormArea id="dataciteSettingsFormArea"}
                <p class="pkp_help">{translate key="plugins.importexport.datacite.settings.description"}</p>
                <p class="pkp_help">{translate key="plugins.importexport.datacite.intro"}</p>
			{fbvFormSection}
            {fbvElement type="text" id="api" value=$api label="plugins.importexport.datacite.settings.form.url" maxlength="100" size=$fbvStyles.size.MEDIUM}
            {fbvElement type="text" id="username" value=$username label="plugins.importexport.datacite.settings.form.username" maxlength="50" size=$fbvStyles.size.MEDIUM}
            {fbvElement type="text" password="true" id="password" value=$password label="plugins.importexport.datacite.settings.form.password" maxLength="50" size=$fbvStyles.size.MEDIUM}
                <span class="instruct">{translate key="plugins.importexport.datacite.settings.form.password.description"}</span>
                <br/>
            {/fbvFormSection}
                <hr>
            {fbvFormSection list="true"}
            {fbvElement type="checkbox" id="testMode" label="plugins.importexport.datacite.settings.form.testMode.description" checked=$testMode|compare:true}
            {/fbvFormSection}
            {fbvElement type="text" id="testRegistry" value=$testRegistry label="plugins.importexport.datacite.settings.form.testRegistry" maxlength="200" size=$fbvStyles.size.MEDIUM}
            {fbvElement type="text" id="testPrefix" value=$testPrefix label="plugins.importexport.datacite.settings.form.testPrefix" maxlength="10" size=$fbvStyles.size.MEDIUM}
            {fbvElement type="text" id="testUrl" value=$testUrl label="plugins.importexport.datacite.settings.form.testUrl" maxlength="200" size=$fbvStyles.size.MEDIUM}
            {/fbvFormArea}
            {fbvFormButtons submitText="common.save"}
        </form>
    </div>
    <div id="monographs-tab">
        <script type="text/javascript">
			$(function () {ldelim}
				$('#monographsForm').pkpHandler('$.pkp.controllers.form.FormHandler');
                {rdelim});
        </script>
		<div class="listing" width="100%">
			<form id="monographsForm" class="pkp_form" action="{plugin_url path="export"}" method="post">
				{csrf}
				<div class="pkp_content_panel">
					<fieldset id="exportFormMonograph">
						<!-- monograph list -->
						<div class="section">
							<div class="pkpListPanel pkpListPanel--select pkpListPanel--selectSubmissions">
								<div class="pkpListPanel__header -pkpClearfix">
									<div class="pkpListPanel__title">{translate key="plugins.importexport.datacite.selectMonograph"}</div>
									<div class="pkpListPanel__search">
										<label>
											<span class="-screenReader">Search</span>
											<input type="search" id="list-panel-search-1" placeholder="Search" class="pkpListPanel__searchInput">
											<span class="pkpListPanel__searchIcons">
												<span aria-hidden="true" class="fa pkpListPanel__searchIcons--search fa-search pkpIcon--inline"></span>
											</span>
										</label>
									</div>
								</div>
								<div class="pkpListPanel__body -pkpClearfix">
									<div class="pkpListPanel__selectAll">
										<div class="pkpListPanelItem__selectItem">
											<input type="checkbox" id="SelectListPanelSelectAllMonograph" class="pkpListPanel__selectAllInput">
										</div>
										<label for="SelectListPanelSelectAllMonograph" class="-screenReader">Select All</label>
									</div>
									<div class="pkpListPanel__content">
										<ul aria-live="polite" class="pkpListPanel__items">
											{foreach $monographsList as $key=>$item}
											<li class="pkpListPanelItem pkpListPanelItem--select pkpListPanelItem--selectSubmission -clearFix">
												<div class="pkpListPanelItem__selectItem">
													<input type="checkbox" id="selectedSubmissions[]{$item["id"]}" name="selectedSubmissions[]" value="{$item["id"]}">
												</div>
												<label for="selectedSubmissions[]" class="pkpListPanelItem__item">
													<div>{$item["id"]}</div>
													<div class="pkpListPanelItem--submission__author">{$item["authors"]}</div>
													<div class="pkpListPanelItem--submission__title">{$item["title"]}</div>
													<div class="pkpListPanelItem--submission__title">doi: {$item["doi"]}</div>
												</label>
												<a href="http://localhost:4444/index.php/dai/workflow/index/{$item["id"]}/5" target="_blank" class="pkpListPanelItem--submission__link">
													<span aria-hidden="true" class="fa fa-external-link-square"></span>
													<span class="-screenReader">View Submission</span>
												</a>
											</li>
											{/foreach}
										</ul>
									</div>
								</div>
								<div class="pkpListPanel__footer -pkpClearfix">
									<div class="pkpListPanel__count">
										{$monographsListSize} monographs
									</div>
								</div>
							</div>
						</div>
						<!-- submit btn -->
						<div class="section formButtons form_buttons ">
							<button class="pkp_button submitFormButton" type="submit" id="submitFormButtonMonograph">Export</button>
							<span class="pkp_spinner"></span>
						</div>
					</fieldset>
				</div>
		 	</form>
		</div>
	</div>
    <div id="chapters-tab">
        <script type="text/javascript">
			$(function () {ldelim}
				$('#chaptersForm').pkpHandler('$.pkp.controllers.form.FormHandler');
                {rdelim});
        </script>
		<div class="listing" width="100%">
			<form id="chaptersForm" class="pkp_form" action="{plugin_url path="export"}" method="post">
				{csrf}
				<div class="pkp_content_panel">
					<fieldset id="exportFormChapter">
						<!-- chapters list -->
						<div class="section">
							<div class="pkpListPanel pkpListPanel--select pkpListPanel--selectSubmissions">
								<div class="pkpListPanel__header -pkpClearfix">
									<div class="pkpListPanel__title">{translate key="plugins.importexport.datacite.selectChapter"}</div>
									<div class="pkpListPanel__search">
										<label>
											<span class="-screenReader">Search</span>
											<input type="search" id="list-panel-search-2" placeholder="Search" class="pkpListPanel__searchInput">
											<span class="pkpListPanel__searchIcons">
												<span aria-hidden="true" class="fa pkpListPanel__searchIcons--search fa-search pkpIcon--inline"></span>
											</span>
										</label>
									</div>
								</div>
								<div class="pkpListPanel__body -pkpClearfix">
									<div class="pkpListPanel__selectAll">
										<div class="pkpListPanelItem__selectItem">
											<input type="checkbox" id="SelectListPanelSelectAllChapters" class="pkpListPanel__selectAllInput">
										</div>
										<label for="SelectListPanelSelectAllChapters" class="-screenReader">Select All</label>
									</div>
									<div class="pkpListPanel__content">
										<ul aria-live="polite" class="pkpListPanel__items">
											{foreach $chaptersList as $key=>$item}
												<li class="pkpListPanelItem pkpListPanelItem--select pkpListPanelItem--selectSubmission -clearFix">
													<div class="pkpListPanelItem__selectItem">
														<input type="checkbox" id="selectedSubmissions[]{$item["id"]}" name="selectedSubmissions[]" value="{$item["id"]}">
													</div>
													<label for="selectedSubmissions[]{$item["id"]}" class="pkpListPanelItem__item">
														<div>{$item["id"]}</div>
														<div class="pkpListPanelItem--submission__author">{$item["authors"]}</div>
														<div class="pkpListPanelItem--submission__title">{$item["title"]}</div>
														<div class="pkpListPanelItem--submission__title">in:
															<span style = "font-style: italic;">{$item["monographTitleOfChapter"]}</span>
														</div>
														<div class="pkpListPanelItem--submission__title">doi: {$item["doi"]}</div>
													</label>
													<a href="http://localhost:4444/index.php/dai/workflow/access/{$item["submissionId"]}" target="_blank" class="pkpListPanelItem--submission__link">
														<span aria-hidden="true" class="fa fa-external-link-square"></span>
														<span class="-screenReader">View Submission</span>
													</a>
												</li>
											{/foreach}
										</ul>
									</div>
								</div>
								<div class="pkpListPanel__footer -pkpClearfix">
									<div class="pkpListPanel__count">
										{$chaptersListSize} Chapters
									</div>
								</div>
							</div>
						</div>
						<div class="section formButtons form_buttons ">
							<button class="pkp_button submitFormButton" type="submit" id="submitFormButtonChapter">Export</button>
							<span class="pkp_spinner"></span>
						</div>
					</fieldset>
				</div>
			</form>
		</div>
    </div>
</div>
{include file="common/footer.tpl"}

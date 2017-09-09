<script type="text/x-template" id="passwords-template-breadcrumb">
    <div id="controls">
        <div class="breadcrumb">
            <div class="crumb svg ui-droppable" data-dir="/">
                <a href="/index.php/apps/passwords">
                    <img class="svg" src="/core/img/places/home.svg" alt="Home">
                </a>
            </div>
            <div class="crumb svg ui-droppable" v-for="item in items">
                <router-link :to="item.link">
                    {{ item.label }}
                </router-link>
            </div>
        </div>
        <div class="actions creatable" v-if="showAddNew">
			<span class="button new" @click="clickAddButton($event)">
				<span class="icon icon-add"></span>
			</span>
            <div class="newPasswordMenu popovermenu bubble menu open menu-left" @click="clickAddButton($event)">
                <ul>
                    <li>
						<span class="menuitem" data-action="folder" v-if="newFolder">
							<span class="icon icon-folder svg"></span>
							<span class="displayname">Neuer Ordner</span>
						</span>
                    </li>
                    <li>
						<span class="menuitem" data-action="tag" v-if="newTag">
							<span class="icon icon-tag svg"></span>
							<span class="displayname">Neuer Tag</span>
						</span>
                    </li>
                    <li>
						<span class="menuitem" data-action="file" @click="clickCreatePassword($event)">
							<span class="icon icon-filetype-text svg"></span>
							<span class="displayname">Neues Passwort</span>
						</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</script>

<script type="text/x-template" id="passwords-template-tags">
    <div class="tags-container">
        Tags
    </div>
</script>

<script type="text/x-template" id="passwords-template-foldout">
    <div class="foldout-container" :data-foldout="name">
        <div class="foldout-title" @click="toggleContent()">
            <i class="fa fa-chevron-right"></i>
            {{title}}
        </div>
        <div class="foldout-content">
            <slot name="content"></slot>
        </div>
    </div>
</script>

<script type="text/x-template" id="passwords-template-password-line">
    <div class="row password" @click="singleClickAction($event)" @dblclick="doubleClickAction()">
        <i class="fa fa-star favourite"
           v-bind:class="{ active: password.favourite }"
          
           @click="favouriteAction($event)"></i>
        <div v-bind:style="faviconStyle" class="favicon">&nbsp;</div>
        <span class="title">{{ password.title }}</span>
        <div class="date">{{ date }}</div>
        <i v-bind:class="securityCheck" class="fa fa-circle security"></i>
        <div class="more" @click="toggleMenu($event)">
            <i class="fa fa-ellipsis-h"></i>
            <div class="passwordActionsMenu popovermenu bubble menu">
                <ul>
                    <li><span><i class="fa fa-info"></i> Details</span></li>
                    <li v-if="password.url" @click="copyUrlAction()"><span><i class="fa fa-clipboard"></i> Copy Url</span></li>
                    <li v-if="password.url"><a :href="password.url" target="_blank"><span><i class="fa fa-link"></i> Open Url</span></a></li>
                    <li><span><i class="fa fa-pencil"></i> Edit</span></li>
                    <li @click="deleteAction()"><span><i class="fa fa-trash"></i> Delete</span></li>
                </ul>
            </div>
        </div>
    </div>
</script>
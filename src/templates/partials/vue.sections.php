
<script type="text/x-template" id="passwords-section-all">
    <div id="content">
        <passwords-breadcrumb></passwords-breadcrumb>
        <div class="content-list">
            <passwords-line-password :password="password" v-for="password in passwords"></passwords-line-password>
        </div>
    </div>
</script>

<script type="text/x-template" id="passwords-section-folders">
    <div id="content">
        <passwords-breadcrumb :newFolder="true"></passwords-breadcrumb>
        <div class="content-list">
            <passwords-line-password :password="password" v-for="password in passwords"></passwords-line-password>
        </div>
    </div>
</script>

<script type="text/x-template" id="passwords-section-tags">
    <div id="content">
        <passwords-breadcrumb :newTag="true"></passwords-breadcrumb>
        <div class="content-list">
            <passwords-line-password :password="password" v-for="password in passwords"></passwords-line-password>
        </div>
    </div>
</script>

<script type="text/x-template" id="passwords-section-recent">
    <div id="content">
        <passwords-breadcrumb></passwords-breadcrumb>
        <div class="content-list">
            <passwords-line-password :password="password" v-for="password in passwords"></passwords-line-password>
        </div>
    </div>
</script>

<script type="text/x-template" id="passwords-section-favourites">
    <div id="content">
        <passwords-breadcrumb></passwords-breadcrumb>
        <div class="content-list">
            <passwords-line-password :password="password" v-for="password in passwords"></passwords-line-password>
        </div>
    </div>
</script>

<script type="text/x-template" id="passwords-section-shared">
    <div id="content">
        <passwords-breadcrumb :showAddNew="false"></passwords-breadcrumb>
        <div class="content-list">
        </div>
    </div>
</script>

<script type="text/x-template" id="passwords-section-security">
    <div id="content">
        <passwords-breadcrumb :showAddNew="false"></passwords-breadcrumb>
        <div class="content-list">
        </div>
    </div>
</script>

<script type="text/x-template" id="passwords-section-trash">
    <div id="content">
        <passwords-breadcrumb :showAddNew="false"></passwords-breadcrumb>
        <div class="content-list">
            <passwords-line-password :password="password" v-for="password in passwords"></passwords-line-password>
        </div>
    </div>
</script>
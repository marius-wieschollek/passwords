<script type="text/x-template" id="passwords-partial-password-create">
    <div class="background" id="passwords-create-new">
        <div class="window">
            <div class="title nc-theming-main-background nc-theming-contrast">
                Create a new password
                <i class="fa fa-times close" aria-hidden="true" @click="closeWindow()"></i>
            </div>
            <form class="content" v-on:submit.prevent="submitCreatePassword($event);">
                <div class="form">
                    <div class="section-title">General</div>
                    <div class="form-grid">
                        <label for="password-title">Name</label>
                        <input id="password-title" type="text" name="title" maxlength="48" value="">
                        <label for="password-login">Username</label>
                        <input id="password-login" type="text" name="login" maxlength="48" value="" required>
                        <label for="password-password">Password</label>
                        <div class="password-field">
                            <div class="icons">
                                <i class="fa fa-eye" aria-hidden="true" @click="togglePasswordVisibility()" title="Toggle visibility"></i>
                                <i class="fa fa-refresh" aria-hidden="true" @click="generateRandomPassword()" title="Generate random password"></i>
                            </div>
                            <input id="password-password" type="password" name="password" maxlength="48" value="" required>
                        </div>
                        <label for="password-url">Website</label>
                        <input id="password-url" type="text" name="url" maxlength="2048" value="">
                        <!-- <passwords-tags></passwords-tags> -->
                        <passwords-foldout name="extraOptions" title="More Options">
                            <div slot="content" class="form-grid">
                                <label for="password-favourite">Favourite</label>
                                <input id="password-favourite" name="favourite" type="checkbox">
                                <label for="password-sse">Encryption</label>
                                <select id="password-sse" name="sse" disabled title="There is only one option right now">
                                    <option value="SSEv1r1" title="Use Simple Server Side Encryption V1" selected>SSE V1</option>
                                </select>
                            </div>
                        </passwords-foldout>
                    </div>
                </div>
                <div class="notes">
                    <label for="password-notes">Notes</label>
                    <textarea id="password-notes" name="notes" maxlength="4096"></textarea>
                </div>
                <div class="controls">
                    <input class="nc-theming-main-background nc-theming-contrast" type="submit" value="Save">
                </div>
            </form>
        </div>
    </div>
</script>
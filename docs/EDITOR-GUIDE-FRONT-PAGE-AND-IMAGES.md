# Editor guide: the front page and article images

For SAHO editors (Jeeva, Leander, Phoenix) and site administrators (Leander, Mads).
Written September 2026 for the current site (Drupal 11, "Open Record" front page).

The old Drupal 7 site let you tick a box and a biography appeared on the front
page. The new front page is built with Layout Builder blocks, and for a while
the checkboxes on the biography form did nothing. That is fixed: the checkboxes
work again, and this guide explains exactly what each one does.

## 1. How the front page picks its content

The front page (node 144647) is a fixed layout of blocks. Editors do not edit the
layout. You control what appears in it from the content itself.

| Front-page section | What decides the content | Who controls it |
|---|---|---|
| **Current feature** (the big lead at the top) | The most recently *saved* published article, archive item or biography with the **"Home Page Feature"** box ticked. | Editors, via the checkbox |
| **Featured biographies** (the band of six) | The six most recently *saved* biographies with the **"Home Page Feature Biography Section"** box ticked. | Editors, via the checkbox (once an administrator has switched the block to "Flagged" mode, see section 5) |
| **Recently added to the archive** | Automatic: the eight newest records by *creation date* across biographies, events, places, archive items and articles. | Nobody. It reflects what was created last |
| This day in history, Most read, Upcoming events, Classroom | Automatic | Nobody |

Two rules to remember:

1. **"Most recently saved" means the last one you pressed Save on.** Re-saving an
   old record with the box ticked moves it to the top. Saving another ticked
   record later bumps it again.
2. **Changes take up to an hour to show** for visitors. The front page is cached
   for one hour on the server and at Cloudflare. Log in and you will usually
   see the change sooner, but do not panic if the public page lags.

## 2. Where the checkboxes are

On any biography or article edit form, open the collapsed **"Home Page Features"**
section near the top. It contains:

- **Home Page Feature** = the *Current feature* slot at the very top of the front
  page. Use this rarely and deliberately. Only one record shows there.
- **Home Page Feature Biography Section** (biographies only) = the *Featured
  biographies* band.
- The other boxes (Africa section, Timeline, Politics and Society, Most Read,
  Staff Picks, Archive Page Feature) feed the /featured register and archive
  pages, not the front page.

## 3. Step by step

**Put a biography in the Featured biographies band**
1. Edit the biography.
2. Open "Home Page Features", tick **Home Page Feature Biography Section**.
3. Save. It becomes the newest ticked biography and enters the band. The
   oldest of the previous six drops out.

**Remove a biography from the band**
1. Edit the biography, untick **Home Page Feature Biography Section**, Save.
2. The next most recently saved ticked biography takes its place.

**Make an article or biography the Current feature**
1. Edit it, tick **Home Page Feature**, Save.
2. Make sure nothing else with that box ticked gets saved afterwards, or it
   will take over. If something else took over by accident, either untick it or
   re-save the one you want.

**Stop something being the Current feature**
1. Edit it, untick **Home Page Feature**, Save. The next most recently saved
   ticked record takes the slot.

**Why did my new article also appear in "Recently added"?**
Because it is new. That band lists the newest records by creation date and
cannot be edited. It will scroll off as newer records are created.

**Common mistake (the Biko case):** ticking **Home Page Feature** on a
biography puts it in the *Current feature* slot, not in the biographies band.
For the band, tick **Home Page Feature Biography Section** instead.

## 4. Article images

The article form has an **"Article Image"** section with:

- **Image** (media library): click **Add media**, then drag a file into the
  upload area or pick an existing image from the library, then **Insert
  selected**. The image appears as a thumbnail with an x to remove it. This is
  the field to use for all new work. It is **optional**, so older articles save
  without one.
- **Legacy article image (old upload)**: only present on articles from before
  the media library. While this field holds an image, that image leads the
  public article page. Remove it (the "Remove" button) and save if you want the
  media **Image** to take over. Nothing is deleted from the archive when you do.
- **Article image caption**: shown under the lead image.

Which image shows on the public page, in order: the legacy upload if present,
otherwise the media **Image**. So on an old article, adding a media Image does
not change what visitors see until the legacy upload is removed. The media
Image is always used for social-sharing previews.

**If you see "You don't have sufficient permissions to use the DropzoneJS
uploader"**: your account is missing the media upload permission. Editors and
superadmins have it since 14 September 2026. Tell an administrator which
account you are using.

## 5. Administrators: switching the biographies block to checkbox mode

Only administrators can open the front-page layout. Editors have no Layout
Builder permission, which is deliberate.

1. Before switching, ask editors to tidy the **Home Page Feature Biography
   Section** checkboxes so that only the biographies they want are ticked
   (32 were ticked in September 2026; the six most recently saved will show).
2. Log in as an administrator and go to `/node/144647/layout`.
3. Scroll to the "Featured biographies" block, hover it, click the pencil, then
   **Configure**.
4. Under **Selection Method** choose **Flagged biographies**. Set **Number of
   Biographies** (six today). Leave **Sort By** on "None" so the newest save
   leads. Click **Update**.
5. Click **Save layout** at the top of the page.
6. Purge the front page at Cloudflare (or wait up to an hour).

To go back to a hand-picked list, choose **Specific Biographies** instead. The
picker is now a type-ahead: start typing a name and pick from the list. Add
several separated by commas.

If the layout ever breaks, `drush saho:frontpage-rebuild` restores the standard
front-page layout and keeps the block settings.

## 6. Roles at a glance

| Role | Can do | Cannot do |
|---|---|---|
| editor (Jeeva) | Create and edit all content, upload media, tick the front-page checkboxes | Open the front-page layout, change blocks |
| researcher | Create and edit articles and biographies | Upload media (no DropzoneJS permission), open layouts |
| administrator (Leander) | Everything, including the front-page layout | - |

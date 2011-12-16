Html2Book adds an option below book page body fields to automatically create 
a new book page for each html heading and subheading in the body text. This 
makes it possible to create a book in a word processing program, then use 
the resulting html to create a multipage Drupal book in a single step.

Each new book page will have the same author, categories, settings, and other 
characteristics of the original page. If Organic Groups is used and the original
page has been assigned to one or more groups, all child book pages will belong to
the same groups.

All text before the first heading will be retained as the body of the original 
page. Subsequent pages will be added as children of that page, using the 
heading as their title and all text from that point to the next heading as 
their body. Child pages will be nested based on the subheadding number, if 
the subheadings are logically organized.

For best results, combine with Html Corrector and HTML Tidy modules. 

EXAMPLE:

Node 1 Title: My Book

Node 1 Body:
<div>Here is my page.</div>
<h1>Page 1</h1>
  <p>Here is my text for page 1.</p>
  <h2>Page 1a</h2>
    <p>This is page 1a.</p>
  <h2>Page 1b</h2>
    <p>This is page 1b.</p>
<h1>Page 2</h1>
  <p>This is page 2.</p>

Will create the following book pages:

Node 1 Title: My Book
Node 1 Body: <div>Here is my page.</div>
Node 1 Parent: <top level>
Node 1 Weight: -15

    Node 2 Title: Page 1
    Node 2 Body: <p>Here is my text for page 1.</p>
    Node 2 Parent: Node 1
    Node 2 Weight: -15

       Node 3 Title: Page 1a
       Node 3 Body: <p>This is page 1a.</p>
       Node 3 Parent: Node 2
       Node 3 Weight: -15

       Node 4 Title: Page 1b
       Node 4 Body: <p>This is page 1b.</p>
       Node 4 Parent: Node 2
       Node 4 Weight: -14

    Node 5 Title: Page 2
    Node 5 Body: <p>This is page 2.</p>
    Node 5 Parent: Node 1
    Node 5 Weight: -14

For best results with html book text pasted from Microsoft Word documents, 
save the document as 'HTML, filtered' and use the Html Tidy module. When 
setting up HTML Tidy, choose the option to clean up Microsoft Word text. 

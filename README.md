# Globit.Newsletter
Newsletter Module based on Psmb.Newsletter Package

##Example Fusion Configuration
```
prototype(Acme.SiteCom:DefaultNewsletterRenderer) < prototype(Psmb.Newsletter:MailRenderer) {

    @context.node =  ${q(node).is('[instanceof TYPO3.Neos:Document]') ?
        node : q(site).find('[instanceof TYPO3.Neos:Document]').get()[0]}

    subject = ${'Newsletter: ' + q(node).property('title')}
    @context.documentNode = ${q(site).find('[instanceof Psmb.Newsletter:SubscriptionPlugin]').closest('[instanceof TYPO3.Neos:Document]').get()[0]}

    body = TYPO3.TypoScript:Template {
        templatePath = 'resource://Acme.SiteCom/Private/Templates/Page/Newsletter.html'
        sectionName = 'Main'

        content {
            // The default content section
            main = PrimaryContent {
                nodePath = 'main'
            }
        }

        node = ${node}
        unsubscribeLink = Psmb.Newsletter:UnsubscribeLink
    }

    # You may automatically inline all css styles.
    body.@process.cssToInline = Psmb.Newsletter:CssToInline {
        cssPath = 'resource://Acme.SiteCom/Public/Styles/newsletter.css'
    }
}
```


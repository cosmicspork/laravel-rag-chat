<Context>
    @foreach($docs as $doc)
    <DocumentPart>
        <FileName>
            {{ $doc['filepath'] }}
        </FileName>
        <Url>
            {{ $doc['url'] }}
        </Url>
        <Content>
            {{ $doc['content'] }}
        </Content>
    </DocumentPart>
    @endforeach()
</Context>

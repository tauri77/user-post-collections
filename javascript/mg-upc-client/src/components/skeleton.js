import { h, Component } from "preact";

export default class Skeleton extends Component {
	static defaultProps = {
		count: 1,
		duration: 1.2,
		width: null,
		wrapper: null,
		height: null,
		circle: false
	};

	render() {
		const elements = [];
		for (let i = 0; i < this.props.count; i++) {
			let style = this.props.styles ? this.props.styles : {}
			if (this.props.width != null) {
				style.width = this.props.width;
			}
			if (this.props.height != null) {
				style.height = this.props.height;
			}
			if (this.props.width !== null && this.props.height !== null && this.props.circle) {
				style.borderRadius = '50%';
			}
			elements.push(
				<span key={i} className={"mg-upc-dg-loading-skeleton"} style={style}>
          &zwnj;
        </span>
			);
		}

		const Wrapper = this.props.wrapper;
		return (
			<span>
        {Wrapper
	        ? elements.map((element, i) => (
		        <Wrapper key={i}>
			        {element}
			        &zwnj;
		        </Wrapper>
	        ))
	        : elements}
      </span>
		);
	}
}